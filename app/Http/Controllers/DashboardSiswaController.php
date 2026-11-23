<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardSiswaController extends Controller
{

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


    private function namaKelas(int $idSiswa): string
    {
        $baris = DB::table('v_roster_presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->first();

        return $baris->nama_kelas ?? 'belum terdaftar';
    }


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



        $jadwalHariIni = $this->jadwalHariIni($idSiswa);
        $riwayatTerakhir = $this->riwayatTerakhir($idSiswa);
        $pemilihanAktif = $this->pemilihanAktif($idSiswa);

        return view('siswa.beranda', compact(
            'siswa', 'kelas', 'ringkasan',
            'jadwalHariIni', 'riwayatTerakhir', 'pemilihanAktif'
        ));
    }


    private function jadwalHariIni(int $idSiswa)
    {
        $hari = Carbon::today()->locale('id')->isoFormat('dddd');

        return DB::table('v_roster_presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->where('hari', strtolower($hari))
            ->orderBy('jam_mulai')
            ->get();
    }


    private function riwayatTerakhir(int $idSiswa, int $batas = 5)
    {
        return DB::table('v_rekap_presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_mulai')
            ->limit($batas)
            ->get();
    }


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
