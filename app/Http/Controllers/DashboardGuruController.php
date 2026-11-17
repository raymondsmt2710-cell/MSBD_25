<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Fitur 9: dashboard guru — HANYA BACA (tombol aksi memakai rute presensi yang sudah ada).
//
// Pembatasan berlapis, sama seperti DashboardSiswaController:
//   1. middleware role:guru      -> hanya akun berperan guru
//   2. koneksi MySQL guru_app    -> hak tulis terbatas pada tabel presensi
//   3. idGuru()                  -> id diambil dari akun yang login, BUKAN dari URL
class DashboardGuruController extends Controller
{
    /** Id guru milik akun yang sedang login. Tidak pernah dibaca dari URL/request. */
    private function idGuru(): int
    {
        $guru = Auth::user()->guru;

        abort_if(
            $guru === null,
            403,
            'Akun ini belum terhubung ke data guru. Hubungi administrator.'
        );

        return (int) $guru->id_guru;
    }

    /** Nama hari ini dalam huruf kecil, cocok dengan kolom jadwal_pelajaran.hari. */
    private function hariIni(): string
    {
        return strtolower(Carbon::now()->locale('id')->isoFormat('dddd'));
    }

    /** Rentang Senin–Sabtu pekan berjalan. Tidak memakai WEEK() agar tidak bergantung mode SQL. */
    private function pekanIni(): array
    {
        $senin = Carbon::now()->startOfWeek(Carbon::MONDAY);

        return [$senin->toDateString(), $senin->copy()->addDays(5)->toDateString()];
    }

    /**
     * Id tahun ajaran aktif, atau null bila tidak ada yang ditandai aktif.
     *
     * Jadwal menempel pada kelas, kelas menempel pada tahun ajaran. Tanpa filter ini
     * jadwal tahun-tahun lalu ikut tampil sebagai baris ganda. Null berarti "jangan
     * difilter", supaya dashboard tidak kosong total bila data tahun ajaran belum diisi.
     */
    private function idTahunAjaranAktif(): ?int
    {
        // Beberapa baris bisa bertanda aktif sekaligus (data lama). Pilih yang paling
        // baru supaya hasilnya deterministik, bukan baris acak.
        $id = DB::table('tahun_ajaran')
            ->where('is_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_tahun_ajaran')
            ->value('id_tahun_ajaran');

        return $id === null ? null : (int) $id;
    }

    /** Membatasi query jadwal ke tahun ajaran aktif, bila ada yang aktif. */
    private function batasiTahunAjaranAktif($q, ?int $idTahunAjaran)
    {
        return $idTahunAjaran === null ? $q : $q->where('k.id_tahun_ajaran', $idTahunAjaran);
    }

    /**
     * Jadwal guru pada hari ini, lengkap dengan sesi hari ini bila sudah dibuat.
     * Left join ke sesi_presensi dibatasi CURDATE() agar tidak mengambil sesi hari lain.
     */
    private function jadwalHariIni(int $idGuru): \Illuminate\Support\Collection
    {
        return $this->batasiTahunAjaranAktif(
            DB::table('jadwal_pelajaran as jp')
                ->join('kelas as k', 'k.id_kelas', '=', 'jp.id_kelas')
                ->join('mata_pelajaran as m', 'm.id_mapel', '=', 'jp.id_mapel')
                ->leftJoin('sesi_presensi as sp', function ($j) {
                    $j->on('sp.id_jadwal', '=', 'jp.id_jadwal')->whereRaw('sp.tanggal = CURDATE()');
                })
                ->where('jp.id_guru', $idGuru)
                ->where('jp.hari', $this->hariIni())
                ->orderBy('jp.jam_mulai')
                ->select(
                    'jp.id_jadwal',
                    'jp.jam_mulai',
                    'jp.jam_selesai',
                    'k.nama_kelas',
                    'm.nama_mapel',
                    'sp.id_sesi',
                    'sp.status as status_sesi'
                ),
            $this->idTahunAjaranAktif()
        )->get();
    }

    /** Mata pelajaran & kelas yang diampu, untuk hero dan data diri. */
    private function kelasDiampu(int $idGuru): \Illuminate\Support\Collection
    {
        return $this->batasiTahunAjaranAktif(
            DB::table('jadwal_pelajaran as jp')
                ->join('kelas as k', 'k.id_kelas', '=', 'jp.id_kelas')
                ->join('mata_pelajaran as m', 'm.id_mapel', '=', 'jp.id_mapel')
                ->where('jp.id_guru', $idGuru)
                ->select('m.nama_mapel', 'k.nama_kelas')
                ->distinct()
                ->orderBy('m.nama_mapel'),
            $this->idTahunAjaranAktif()
        )->get();
    }

    /** Jumlah sesi milik guru, dengan filter tambahan opsional. */
    private function jumlahSesi(int $idGuru, ?array $rentang = null, ?string $status = null): int
    {
        $q = DB::table('sesi_presensi as sp')
            ->join('jadwal_pelajaran as jp', 'jp.id_jadwal', '=', 'sp.id_jadwal')
            ->where('jp.id_guru', $idGuru);

        if ($rentang !== null) {
            $q->whereBetween('sp.tanggal', $rentang);
        }

        if ($status !== null) {
            $q->where('sp.status', $status);
        }

        return $q->count();
    }

    /**
     * Sesi yang masih dibuka TETAPI belum tampil di bagian "Jadwal Hari Ini".
     *
     * Kartu "Sesi Terbuka" menghitung seluruh sesi berstatus dibuka, jadi setiap sesi
     * harus bisa ditindaklanjuti dari halaman ini. Sesi hari ini sudah punya tombol
     * QR/Tutup di baris jadwal, sehingga $kecuali (id sesi yang sudah dirender di sana)
     * dikeluarkan agar tidak muncul dua kali. sp_tutup_sesi tidak membatasi tanggal,
     * jadi sesi yang tertinggal dari hari sebelumnya tetap bisa ditutup dari sini.
     */
    private function sesiTerbuka(int $idGuru, array $kecuali = []): \Illuminate\Support\Collection
    {
        $q = DB::table('sesi_presensi as sp')
            ->join('jadwal_pelajaran as jp', 'jp.id_jadwal', '=', 'sp.id_jadwal')
            ->join('kelas as k', 'k.id_kelas', '=', 'jp.id_kelas')
            ->join('mata_pelajaran as m', 'm.id_mapel', '=', 'jp.id_mapel')
            ->where('jp.id_guru', $idGuru)
            ->where('sp.status', 'dibuka');

        if ($kecuali !== []) {
            $q->whereNotIn('sp.id_sesi', $kecuali);
        }

        return $q->orderByDesc('sp.tanggal')
            ->orderBy('jp.jam_mulai')
            ->select(
                'sp.id_sesi',
                'sp.tanggal',
                'k.nama_kelas',
                'm.nama_mapel',
                'jp.jam_mulai',
                'jp.jam_selesai'
            )
            ->get();
    }

    /**
     * Rata-rata kehadiran pekan ini dari view rekap.
     *
     * Hanya sesi yang sudah DITUTUP yang dihitung: sesi yang masih dibuka punya
     * total_tercatat sebagian, sehingga memasukkannya menurunkan persentase di
     * tengah pelajaran tanpa alasan.
     * total_tercatat = 0 berarti belum ada presensi; kembalikan 0.0, bukan pembagian nol.
     */
    private function kehadiranPekanIni(int $idGuru, array $rentang): float
    {
        $baris = DB::table('v_rekap_sesi_presensi')
            ->where('id_guru', $idGuru)
            ->where('status_sesi', 'ditutup')
            ->whereBetween('tanggal', $rentang)
            ->selectRaw('COALESCE(SUM(hadir), 0) AS hadir, COALESCE(SUM(total_tercatat), 0) AS total')
            ->first();

        $total = (int) ($baris->total ?? 0);

        if ($total === 0) {
            return 0.0;
        }

        return round(((float) $baris->hadir) * 100 / $total, 2);
    }

    public function index()
    {
        $idGuru = $this->idGuru();

        $guru = DB::table('guru')->where('id_guru', $idGuru)->first();

        abort_if($guru === null, 403, 'Data guru tidak ditemukan.');

        $rentang = $this->pekanIni();
        $jadwalHariIni = $this->jadwalHariIni($idGuru);

        $ringkasan = [
            'jadwal_hari_ini' => $jadwalHariIni->count(),
            'sesi_terbuka' => $this->jumlahSesi($idGuru, null, 'dibuka'),
            'sesi_pekan_ini' => $this->jumlahSesi($idGuru, $rentang),
            'kehadiran_pekan_ini' => $this->kehadiranPekanIni($idGuru, $rentang),
        ];

        // Hanya sesi yang sudah ditutup: rekap berarti angka yang sudah final.
        // Sesi yang masih dibuka punya total_tercatat sebagian, dan sudah punya
        // bagiannya sendiri ("Sesi Masih Terbuka") di halaman ini.
        $rekapSesi = DB::table('v_rekap_sesi_presensi')
            ->where('id_guru', $idGuru)
            ->where('status_sesi', 'ditutup')
            ->orderByDesc('tanggal')
            ->orderByDesc('id_sesi')
            ->limit(5)
            ->get();

        $sesiTerbuka = $this->sesiTerbuka(
            $idGuru,
            $jadwalHariIni->pluck('id_sesi')->filter()->all()
        );
        $kelasDiampu = $this->kelasDiampu($idGuru);

        return view('guru.beranda', compact(
            'guru',
            'kelasDiampu',
            'ringkasan',
            'jadwalHariIni',
            'rekapSesi',
            'sesiTerbuka'
        ));
    }
}
