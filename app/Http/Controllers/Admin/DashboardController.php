<?php

namespace App\Http\Controllers\Admin;

use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

class DashboardController extends AdminController
{
    public function index()
    {
        $tahunAktif = TahunAjaran::aktif();

        $akun = DB::table('users')
            ->selectRaw('role, SUM(is_active = 1) AS aktif, COUNT(*) AS total')
            ->groupBy('role')
            ->get()
            ->keyBy('role');

        $statistik = [
            'Siswa aktif' => DB::table('siswa')->where('status', 'aktif')->count(),
            'Guru' => DB::table('guru')->count(),
            'Orang tua / wali' => DB::table('orang_tua')->count(),
            'Kelas (TA aktif)' => $tahunAktif
                ? DB::table('kelas')->where('id_tahun_ajaran', $tahunAktif->id_tahun_ajaran)->count()
                : 0,
            'Organisasi' => DB::table('organisasi')->count(),
            'Pemilihan berlangsung' => DB::table('pemilihan')->where('status', 'berlangsung')->count(),
        ];

        // Hal yang perlu ditindaklanjuti admin: data yang belum lengkap.
        $peringatan = array_filter([
            $tahunAktif ? null : 'Belum ada tahun ajaran yang ditandai aktif.',
            $this->hitungAkunTanpaProfil() ?: null,
            $tahunAktif ? $this->siswaTanpaKelas($tahunAktif->id_tahun_ajaran) : null,
            $tahunAktif ? $this->kelasTanpaWali($tahunAktif->id_tahun_ajaran) : null,
        ]);

        $izinMenunggu = DB::table('pengajuan_izin')->where('status', 'menunggu')->count();

        return view('admin.dashboard', compact('tahunAktif', 'akun', 'statistik', 'peringatan', 'izinMenunggu'));
    }

    /** Akun siswa/guru/orang tua yang belum dibuatkan profil (tidak bisa dipakai penuh). */
    private function hitungAkunTanpaProfil(): ?string
    {
        $jumlah = DB::table('users as u')
            ->leftJoin('siswa as s', 's.id_user', '=', 'u.id_user')
            ->leftJoin('guru as g', 'g.id_user', '=', 'u.id_user')
            ->leftJoin('orang_tua as o', 'o.id_user', '=', 'u.id_user')
            ->where(function ($q) {
                $q->where(fn ($w) => $w->where('u.role', 'siswa')->whereNull('s.id_siswa'))
                    ->orWhere(fn ($w) => $w->where('u.role', 'guru')->whereNull('g.id_guru'))
                    ->orWhere(fn ($w) => $w->where('u.role', 'orang_tua')->whereNull('o.id_orang_tua'));
            })
            ->count();

        return $jumlah ? "{$jumlah} akun belum memiliki profil (siswa/guru/orang tua)." : null;
    }

    private function siswaTanpaKelas(int $idTahun): ?string
    {
        $jumlah = DB::table('siswa as s')
            ->where('s.status', 'aktif')
            ->whereNotExists(function ($q) use ($idTahun) {
                $q->from('anggota_kelas as ak')
                    ->join('kelas as k', 'k.id_kelas', '=', 'ak.id_kelas')
                    ->whereColumn('ak.id_siswa', 's.id_siswa')
                    ->where('k.id_tahun_ajaran', $idTahun);
            })
            ->count();

        return $jumlah ? "{$jumlah} siswa aktif belum ditempatkan di kelas pada tahun ajaran aktif." : null;
    }

    private function kelasTanpaWali(int $idTahun): ?string
    {
        $jumlah = DB::table('kelas')->where('id_tahun_ajaran', $idTahun)->whereNull('id_wali_kelas')->count();

        return $jumlah ? "{$jumlah} kelas belum memiliki wali kelas." : null;
    }
}
