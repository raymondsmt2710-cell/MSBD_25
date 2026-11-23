<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PortalOrangTuaController extends Controller
{

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


    private function pastikanAnakMilik(int $idOrangTua, int $idSiswa): object
    {
        $anak = DB::table('v_portal_anak_orang_tua')
            ->where('id_orang_tua', $idOrangTua)
            ->where('id_siswa', $idSiswa)
            ->first();

        abort_if($anak === null, 404, 'Data anak tidak ditemukan.');

        return $anak;
    }


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


    public function anak(Request $request, int $idSiswa)
    {
        $idOrangTua = $this->idOrangTua();
        $anak = $this->pastikanAnakMilik($idOrangTua, $idSiswa);



        $bulan = (string) $request->query('bulan', '');
        $bulan = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan) ? $bulan : '';

        $riwayat = DB::table('v_rekap_presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->when($bulan !== '', function ($q) use ($bulan) {


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
