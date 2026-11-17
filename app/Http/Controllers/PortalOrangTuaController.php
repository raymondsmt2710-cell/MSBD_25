<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Fitur 7: pemantauan orang tua/wali — HANYA BACA.
//
// Tidak ada satu pun rute tulis di sini. Orang tua hanya bisa melihat daftar anaknya
// sendiri dan riwayat presensi anak tersebut. Pembatasan dilakukan berlapis:
//   1. middleware role:orang_tua  -> hanya akun berperan orang_tua
//   2. koneksi MySQL ortu_app     -> SELECT-only, jadi kalaupun ada bug di kode,
//                                    database menolak penulisan
//   3. idOrangTua()               -> id orang tua diambil dari akun yang login,
//                                    BUKAN dari parameter URL
//   4. pastikanAnakMilik()        -> id_siswa di URL diverifikasi milik orang tua itu
class PortalOrangTuaController extends Controller
{
    /**
     * Id orang tua milik akun yang sedang login.
     *
     * Sengaja tidak pernah membaca id orang tua dari URL/request: kalau dibaca dari
     * request, orang tua bisa mengganti angka di URL dan melihat anak orang lain.
     */
    private function idOrangTua(): int
    {
        $orangTua = Auth::user()->orangTua;

        abort_if(
            $orangTua === null,
            403,
            'Akun ini belum terhubung ke data orang tua/wali. Hubungi administrator.'
        );

        return (int) $orangTua->id_orang_tua;
    }

    /**
     * Pastikan siswa pada URL benar-benar anak dari orang tua yang login.
     *
     * Memakai view v_portal_anak_orang_tua (bukan tabel langsung) supaya pemeriksaan
     * kepemilikan dan pengambilan data memakai sumber yang sama. Hasilnya 404, bukan
     * 403, agar keberadaan id siswa lain tidak bisa ditebak dari beda kode status.
     */
    private function pastikanAnakMilik(int $idOrangTua, int $idSiswa): object
    {
        $anak = DB::table('v_portal_anak_orang_tua')
            ->where('id_orang_tua', $idOrangTua)
            ->where('id_siswa', $idSiswa)
            ->first();

        abort_if($anak === null, 404, 'Data anak tidak ditemukan.');

        return $anak;
    }

    /**
     * Ringkasan kehadiran lewat Stored Function (materi Fungsi & Stored Procedure).
     * fn_persentase_kehadiran dan fn_jumlah_presensi dihitung di sisi MySQL.
     */
    private function ringkasan(int $idSiswa): array
    {
        return [
            'persen' => Presensi::persentaseKehadiran($idSiswa),
            'hadir'  => Presensi::jumlahStatus($idSiswa, 'hadir'),
            'izin'   => Presensi::jumlahStatus($idSiswa, 'izin'),
            'sakit'  => Presensi::jumlahStatus($idSiswa, 'sakit'),
            'alpa'   => Presensi::jumlahStatus($idSiswa, 'alpa'),
        ];
    }

    /** Halaman 1: daftar anak milik orang tua yang login. */
    public function index()
    {
        $idOrangTua = $this->idOrangTua();

        $anak = DB::table('v_portal_anak_orang_tua')
            ->where('id_orang_tua', $idOrangTua)
            ->orderBy('nama_siswa')
            ->get()
            ->map(function ($a) {
                $a->ringkasan = $this->ringkasan((int) $a->id_siswa);

                return $a;
            });

        $orangTua = DB::table('orang_tua')->where('id_orang_tua', $idOrangTua)->first();

        return view('ortu.index', compact('anak', 'orangTua'));
    }

    /** Halaman 2: rincian satu anak — profil singkat + riwayat presensi. */
    public function anak(Request $request, int $idSiswa)
    {
        $idOrangTua = $this->idOrangTua();
        $anak = $this->pastikanAnakMilik($idOrangTua, $idSiswa);

        // Saringan bulan bersifat opsional. Formatnya divalidasi ketat (YYYY-MM) supaya
        // nilai aneh tidak pernah sampai ke query, dan bulan di luar 01–12 ditolak.
        $bulan = (string) $request->query('bulan', '');
        $bulan = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan) ? $bulan : '';

        $riwayat = DB::table('v_rekap_presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->when($bulan !== '', function ($q) use ($bulan) {
                // Awal bulan dan awal bulan berikutnya: lebih aman daripada LIKE pada
                // kolom tanggal karena tetap bisa memakai index.
                $awal = $bulan.'-01';
                $akhir = date('Y-m-d', strtotime($awal.' +1 month'));

                $q->where('tanggal', '>=', $awal)->where('tanggal', '<', $akhir);
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('id_presensi')
            ->paginate(15)
            ->withQueryString();

        $ringkasan = $this->ringkasan($idSiswa);

        return view('ortu.anak', compact('anak', 'riwayat', 'ringkasan', 'bulan'));
    }
}
