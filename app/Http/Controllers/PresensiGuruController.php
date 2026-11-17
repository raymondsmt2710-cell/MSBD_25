<?php

namespace App\Http\Controllers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresensiGuruController extends Controller
{
    private function idGuru(): int
    {
        return Auth::user()->guru?->id_guru ?? abort(403, 'Akun ini belum terhubung ke data guru.');
    }

    /** Sesi harus milik guru yang sedang login. */
    private function sesiMilik(int $idSesi): object
    {
        return DB::table('sesi_presensi as sp')
            ->join('jadwal_pelajaran as jp', 'jp.id_jadwal', '=', 'sp.id_jadwal')
            ->join('kelas as k', 'k.id_kelas', '=', 'jp.id_kelas')
            ->join('mata_pelajaran as m', 'm.id_mapel', '=', 'jp.id_mapel')
            ->where('sp.id_sesi', $idSesi)
            ->where('jp.id_guru', $this->idGuru())
            ->select('sp.*', 'jp.id_kelas', 'jp.jam_mulai', 'jp.jam_selesai', 'k.nama_kelas', 'm.nama_mapel')
            ->first() ?? abort(404);
    }

    /**
     * Roster presensi: seluruh jadwal guru (berulang tiap pekan), dengan penanda
     * apakah sesi untuk hari ini sudah dibuat.
     */
    public function index()
    {
        $roster = DB::table('jadwal_pelajaran as jp')
            ->join('kelas as k', 'k.id_kelas', '=', 'jp.id_kelas')
            ->join('mata_pelajaran as m', 'm.id_mapel', '=', 'jp.id_mapel')
            ->leftJoin('sesi_presensi as sp', function ($j) {
                $j->on('sp.id_jadwal', '=', 'jp.id_jadwal')->whereRaw('sp.tanggal = CURDATE()');
            })
            ->where('jp.id_guru', $this->idGuru())
            ->orderByRaw("FIELD(jp.hari, 'senin','selasa','rabu','kamis','jumat','sabtu')")
            ->orderBy('jp.jam_mulai')
            ->select(
                'jp.id_jadwal', 'jp.hari', 'jp.jam_mulai', 'jp.jam_selesai',
                'k.nama_kelas', 'm.nama_mapel', 'sp.id_sesi', 'sp.status as status_sesi'
            )
            ->get();

        $hariIni = $this->hariIni();

        return view('guru.presensi.index', compact('roster', 'hariIni'));
    }

    /**
     * Membuat sesi presensi untuk jadwal pada baris roster yang diklik.
     * Tanggal memakai CURDATE(); jam buka/tutup mengikuti jam jadwal.
     */
    public function buka(int $idJadwal)
    {
        try {
            DB::beginTransaction();

            DB::statement('CALL sp_buka_sesi(?, ?, @status, @id_sesi)', [$idJadwal, $this->idGuru()]);
            $hasil = DB::selectOne('SELECT @status AS status, @id_sesi AS id_sesi');

            if (in_array($hasil->status, ['BERHASIL', 'SESI_SUDAH_ADA'], true)) {
                // QR statis: token dibuat sekali, berlaku sampai jam selesai jadwal.
                $this->pastikanToken($hasil->id_sesi);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return back()->with('error', 'Gagal membuka sesi: '.$e->getMessage());
        }

        if (in_array($hasil->status, ['BERHASIL', 'SESI_SUDAH_ADA'], true)) {
            return redirect()->route('guru.presensi.sesi', $hasil->id_sesi);
        }

        $pesan = $hasil->status === 'BUKAN_HARI_JADWAL'
            ? 'Sesi hanya bisa dibuka pada hari jadwal.'
            : 'Jadwal tidak valid untuk akun Anda.';

        return back()->with('error', $pesan);
    }

    /**
     * Memastikan sesi memiliki tepat satu token aktif yang berlaku sampai jam selesai.
     * Kode dibuat acak 64 hex sehingga tidak pernah sama antar sesi.
     */
    private function pastikanToken(int $idSesi): void
    {
        $ada = DB::table('token_presensi')->where('id_sesi', $idSesi)->exists();

        if ($ada) {
            return;
        }

        $sesi = DB::table('sesi_presensi as sp')
            ->join('jadwal_pelajaran as jp', 'jp.id_jadwal', '=', 'sp.id_jadwal')
            ->where('sp.id_sesi', $idSesi)
            ->select('sp.tanggal', 'jp.jam_selesai')
            ->first();

        if (! $sesi) {
            return;
        }

        $kode = bin2hex(random_bytes(32)); // 64 karakter hex
        $kedaluwarsa = $sesi->tanggal.' '.$sesi->jam_selesai;

        DB::table('token_presensi')->insert([
            'id_sesi'          => $idSesi,
            'kode_token'       => $kode,
            'dibuat_pada'      => DB::raw('NOW()'),
            'kedaluwarsa_pada' => $kedaluwarsa,
        ]);
    }

    public function tampil(int $idSesi)
    {
        $sesi = $this->sesiMilik($idSesi);

        $siswa = DB::table('anggota_kelas as ak')
            ->join('siswa as s', 's.id_siswa', '=', 'ak.id_siswa')
            ->leftJoin('presensi as p', function ($j) use ($idSesi) {
                $j->on('p.id_siswa', '=', 'ak.id_siswa')->where('p.id_sesi', '=', $idSesi);
            })
            ->where('ak.id_kelas', $sesi->id_kelas)
            ->orderBy('ak.no_absen')->orderBy('s.nama_siswa')
            ->select(
                's.id_siswa', 's.nis', 's.nama_siswa', 'ak.no_absen',
                'p.status', 'p.metode', 'p.waktu_presensi',
                'p.latitude', 'p.longitude', 'p.akurasi_meter'
            )
            ->get();

        $token = DB::table('token_presensi')
            ->where('id_sesi', $idSesi)
            ->orderByDesc('id_token')
            ->first();

        $qrSvg = null;
        if ($token && $sesi->status === 'dibuka') {
            $qrSvg = $this->svgQr(route('siswa.presensi.konfirmasi', $token->kode_token));
        }

        return view('guru.presensi.sesi', compact('sesi', 'siswa', 'token', 'qrSvg'));
    }

    private function svgQr(string $isi): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(320, 1), new SvgImageBackEnd())))
            ->writeString($isi);

        return trim(preg_replace('/<\?xml.*?\?>/s', '', $svg));
    }

    /** Dipakai halaman guru untuk memperbarui jumlah hadir tanpa memuat ulang halaman. */
    public function qr(int $idSesi)
    {
        $sesi = $this->sesiMilik($idSesi);

        if ($sesi->status !== 'dibuka') {
            return response()->json(['status' => 'DITUTUP']);
        }

        $token = DB::table('token_presensi')
            ->where('id_sesi', $idSesi)
            ->orderByDesc('id_token')
            ->first();

        if (! $token) {
            return response()->json(['status' => 'TANPA_TOKEN'], 409);
        }

        $hadir = DB::table('presensi')->where('id_sesi', $idSesi)->where('status', 'hadir')->count();

        return response()->json([
            'status' => 'OK',
            'kode'   => $token->kode_token,
            'hadir'  => $hadir,
            'belum'  => DB::table('anggota_kelas')
                ->join('jadwal_pelajaran as jp', 'jp.id_kelas', '=', 'anggota_kelas.id_kelas')
                ->where('jp.id_jadwal', $sesi->id_jadwal)
                ->count() - $hadir,
        ]);
    }

    /** Menutup sesi memakai transaksi + Stored Procedure sp_tutup_sesi */
    public function tutup(int $idSesi)
    {
        $this->sesiMilik($idSesi);

        try {
            DB::beginTransaction();
            DB::statement('CALL sp_tutup_sesi(?, ?, @status, @jumlah)', [$idSesi, $this->idGuru()]);
            $hasil = DB::selectOne('SELECT @status AS status, @jumlah AS jumlah');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return back()->with('error', 'Gagal menutup sesi: '.$e->getMessage());
        }

        return $hasil->status === 'BERHASIL'
            ? back()->with('success', "Sesi ditutup. {$hasil->jumlah} siswa dicatat otomatis (izin/sakit/alpa).")
            : back()->with('error', 'Sesi sudah ditutup atau tidak valid.');
    }

    /** Koreksi manual oleh guru (metode = manual, diubah_oleh = id_guru) */
    public function manual(Request $request, int $idSesi)
    {
        $sesi = $this->sesiMilik($idSesi);

        $data = $request->validate([
            'id_siswa' => 'required|integer',
            'status'   => 'required|in:hadir,izin,sakit,alpa',
            'catatan'  => 'nullable|string|max:255',
        ]);

        $anggota = DB::table('anggota_kelas')
            ->where('id_kelas', $sesi->id_kelas)->where('id_siswa', $data['id_siswa'])->exists();

        if (! $anggota) {
            return back()->with('error', 'Siswa bukan anggota kelas ini.');
        }

        try {
            DB::beginTransaction();
            DB::table('presensi')->updateOrInsert(
                ['id_sesi' => $idSesi, 'id_siswa' => $data['id_siswa']],
                [
                    'status'         => $data['status'],
                    'metode'         => 'manual',
                    'waktu_presensi' => DB::raw('NOW()'),
                    'diubah_oleh'    => $this->idGuru(),
                    'catatan'        => $data['catatan'] ?? null,
                ]
            );
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return back()->with('error', 'Gagal menyimpan: '.$e->getMessage());
        }

        return back()->with('success', 'Presensi diperbarui.');
    }

    private function hariIni(): ?string
    {
        return [1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu'][now()->dayOfWeek] ?? null;
    }
}
