<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
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

        return view('siswa.beranda', compact('siswa', 'kelas', 'ringkasan'));
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
