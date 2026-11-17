<?php

namespace App\Http\Controllers;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresensiGuruController extends Controller
{
    private const DURASI_TOKEN_DETIK = 60;

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
            ->select('sp.*', 'jp.id_kelas', 'k.nama_kelas', 'm.nama_mapel')
            ->first() ?? abort(404);
    }

    public function index()
    {
        $jadwal = DB::table('jadwal_pelajaran as jp')
            ->join('kelas as k', 'k.id_kelas', '=', 'jp.id_kelas')
            ->join('mata_pelajaran as m', 'm.id_mapel', '=', 'jp.id_mapel')
            ->leftJoin('sesi_presensi as sp', function ($j) {
                $j->on('sp.id_jadwal', '=', 'jp.id_jadwal')->whereRaw('sp.tanggal = CURDATE()');
            })
            ->where('jp.id_guru', $this->idGuru())
            ->orderByRaw("FIELD(jp.hari, 'senin','selasa','rabu','kamis','jumat','sabtu')")
            ->orderBy('jp.jam_mulai')
            ->select('jp.id_jadwal', 'jp.hari', 'jp.jam_mulai', 'jp.jam_selesai',
                     'k.nama_kelas', 'm.nama_mapel', 'sp.id_sesi', 'sp.status as status_sesi')
            ->get();

        $hariIni = [1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu'][now()->dayOfWeek] ?? null;

        return view('guru.presensi.index', compact('jadwal', 'hariIni'));
    }

    /** Memanggil Stored Procedure sp_buka_sesi */
    public function buka(int $idJadwal)
    {
        try {
            DB::statement('CALL sp_buka_sesi(?, ?, @status, @id_sesi)', [$idJadwal, $this->idGuru()]);
            $hasil = DB::selectOne('SELECT @status AS status, @id_sesi AS id_sesi');
        } catch (\Throwable $e) {
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
            ->select('s.id_siswa', 's.nis', 's.nama_siswa', 'ak.no_absen', 'p.status', 'p.metode', 'p.waktu_presensi')
            ->get();

        return view('guru.presensi.sesi', compact('sesi', 'siswa'));
    }

    /**
     * Dipanggil berkala oleh halaman guru. Memberi QR dari token yang masih segar & belum dipakai;
     * jika token sudah terpakai / hampir kedaluwarsa, dibuat token baru lewat sp_buat_token_presensi.
     */
    public function qr(int $idSesi)
    {
        $sesi = $this->sesiMilik($idSesi);

        if ($sesi->status !== 'dibuka') {
            return response()->json(['status' => 'DITUTUP']);
        }

        $token = DB::table('token_presensi')
            ->where('id_sesi', $idSesi)
            ->whereNull('digunakan_oleh')
            ->whereRaw('kedaluwarsa_pada > NOW() + INTERVAL 10 SECOND')
            ->orderByDesc('id_token')
            ->first();

        $kode = $token?->kode_token;

        if (! $kode) {
            $kode = bin2hex(random_bytes(32)); // 64 karakter hex
            DB::statement('CALL sp_buat_token_presensi(?, ?, ?, ?, @status)',
                [$idSesi, $this->idGuru(), $kode, self::DURASI_TOKEN_DETIK]);
            $status = DB::selectOne('SELECT @status AS status')->status;

            if ($status !== 'BERHASIL') {
                return response()->json(['status' => $status], 409);
            }
        }

        $svg = (new Writer(new ImageRenderer(new RendererStyle(320, 1), new SvgImageBackEnd())))
            ->writeString(route('siswa.presensi.konfirmasi', $kode));
        $svg = preg_replace('/<\?xml.*?\?>/s', '', $svg);

        $hadir = DB::table('presensi')->where('id_sesi', $idSesi)->where('status', 'hadir')->count();

        return response()->json(['status' => 'OK', 'kode' => $kode, 'svg' => trim($svg), 'hadir' => $hadir]);
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
}
