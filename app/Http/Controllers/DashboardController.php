<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Support\Akses;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly Akses $akses) {}

    public function admin(Request $request): View
    {
        return view('dashboard.admin', [
            'totalKelas' => $this->akses->daftarKelas($request->user())->count(),
            'totalSiswa' => Siswa::query()->where('status', 'aktif')->count(),
            'kelas' => $this->akses->daftarKelas($request->user())->take(5),
        ]);
    }

    public function guru(Request $request): View
    {
        $guru = $request->user()->guru;

        return view('dashboard.guru', [
            'guru' => $guru,
            'jumlahJadwal' => $guru?->jadwalPelajaran()->count() ?? 0,
            'kelas' => $this->akses->daftarKelas($request->user()),
        ]);
    }

    public function siswa(Request $request): View
    {
        $kelas = $this->akses->kelasAktifSiswa($request->user());

        return view('dashboard.siswa', [
            'siswa' => $request->user()->siswa,
            'kelas' => $kelas,
        ]);
    }

    public function ortu(Request $request): View
    {
        $orangTua = $request->user()->orangTua;
        $anak = $orangTua?->siswa()->with(['anggotaKelas.kelas'])->get() ?? collect();

        return view('dashboard.ortu', [
            'orangTua' => $orangTua,
            'anak' => $anak,
            'kelas' => $this->akses->daftarKelas($request->user()),
        ]);
    }
}
