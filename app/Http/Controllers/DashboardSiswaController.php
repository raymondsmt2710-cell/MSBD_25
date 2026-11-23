<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Fitur 8: dashboard siswa — HANYA BACA.
//
// Pembatasan berlapis, sama seperti PortalOrangTuaController:
//   1. middleware role:siswa      -> hanya akun berperan siswa
//   2. koneksi MySQL siswa_app    -> SELECT-only di sisi database
//   3. idSiswa()                  -> id diambil dari akun yang login, BUKAN dari URL
class DashboardSiswaController extends Controller
{
    /**
     * Id siswa milik akun yang sedang login.
     *
     * Sengaja tidak pernah membaca id siswa dari URL/request: kalau dibaca dari
     * request, siswa bisa mengganti angka di URL dan melihat data siswa lain.
     */
    private function idSiswa(): int
    {
        $siswa = Auth::user()->siswa;

        abort_if(
            $siswa === null,
            403,
            'Akun ini belum terhubung ke data siswa. Hubungi administrator.'
        );

        return (int) $siswa->id_siswa;
    }

    /** Nama kelas dari view roster; "belum terdaftar" bila siswa belum punya kelas. */
    private function namaKelas(int $idSiswa): string
    {
        $baris = DB::table('v_roster_presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->first();

        return $baris->nama_kelas ?? 'belum terdaftar';
    }

    /** Ringkasan kehadiran lewat stored function (materi Fungsi & Stored Procedure). */
    private function ringkasan(int $idSiswa): array
    {
        return [
            'persen' => Presensi::persentaseKehadiran($idSiswa),
            'hadir' => Presensi::jumlahStatus($idSiswa, 'hadir'),
            'izin' => Presensi::jumlahStatus($idSiswa, 'izin'),
            'sakit' => Presensi::jumlahStatus($idSiswa, 'sakit'),
            'alpa' => Presensi::jumlahStatus($idSiswa, 'alpa'),
        ];
    }

    public function index()
    {
        $idSiswa = $this->idSiswa();

        $siswa = DB::table('siswa')->where('id_siswa', $idSiswa)->first();

        abort_if($siswa === null, 403, 'Data siswa tidak ditemukan.');

        $kelas = $this->namaKelas($idSiswa);
        $ringkasan = $this->ringkasan($idSiswa);

        // Tiga bagian pengisi beranda. Semuanya hanya-baca dan dibatasi id_siswa
        // milik akun yang login, sama seperti bagian statistik di atas.
        $jadwalHariIni = $this->jadwalHariIni($idSiswa);
        $riwayatTerakhir = $this->riwayatTerakhir($idSiswa);
        $pemilihanAktif = $this->pemilihanAktif($idSiswa);

        return view('siswa.beranda', compact(
            'siswa', 'kelas', 'ringkasan',
            'jadwalHariIni', 'riwayatTerakhir', 'pemilihanAktif'
        ));
    }

    /**
     * Jadwal pelajaran kelas siswa pada hari ini.
     *
     * Roster disimpan per nama hari (senin..sabtu), bukan per tanggal, jadi
     * pencocokannya memakai nama hari dari tanggal hari ini.
     */
    private function jadwalHariIni(int $idSiswa)
    {
        $hari = Carbon::today()->locale('id')->isoFormat('dddd');

        return DB::table('v_roster_presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->where('hari', strtolower($hari))
            ->orderBy('jam_mulai')
            ->get();
    }

    /** Lima presensi terakhir milik siswa, terbaru lebih dulu. */
    private function riwayatTerakhir(int $idSiswa, int $batas = 5)
    {
        return DB::table('v_rekap_presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_mulai')
            ->limit($batas)
            ->get();
    }

    /**
     * Pemilihan yang sedang berlangsung atau masih draf dan diikuti siswa ini.
     *
     * Dipakai untuk memberi tahu siswa bahwa ada pemilihan yang menunggu,
     * sekaligus menampilkan apakah suaranya sudah tercatat. Hanya satu yang
     * ditampilkan karena itu yang paling perlu ditindaklanjuti.
     */
    private function pemilihanAktif(int $idSiswa): ?object
    {
        return DB::table('pemilihan as p')
            ->join('daftar_pemilih as dp', function ($j) use ($idSiswa) {
                $j->on('dp.id_pemilihan', '=', 'p.id_pemilihan')
                    ->where('dp.id_siswa', '=', $idSiswa);
            })
            ->whereIn('p.status', ['berlangsung', 'draf'])
            ->orderByRaw("FIELD(p.status, 'berlangsung', 'draf')")
            ->orderByDesc('p.waktu_mulai')
            ->select('p.id_pemilihan', 'p.judul', 'p.status', 'p.waktu_selesai', 'dp.sudah_memilih')
            ->first();
    }

    public function perangkat()
    {
        $idSiswa = $this->idSiswa();

        $siswa = DB::table('siswa')->where('id_siswa', $idSiswa)->first();

        abort_if($siswa === null, 403, 'Data siswa tidak ditemukan.');

        $kelas = DB::table('v_perangkat_kelas_siswa')
            ->where('id_siswa', $idSiswa)
            ->orderBy('urutan')
            ->get();

        $organisasi = DB::table('v_perangkat_organisasi_siswa')
            ->where('id_siswa', $idSiswa)
            ->orderBy('urutan')
            ->get();

        return view('siswa.perangkat', compact('siswa', 'kelas', 'organisasi'));
    }
}
