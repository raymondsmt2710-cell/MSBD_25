<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardGuruController extends Controller
{

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


    private function hariIni(): string
    {
        return strtolower(Carbon::now()->locale('id')->isoFormat('dddd'));
    }


    private function pekanIni(): array
    {
        $senin = Carbon::now()->startOfWeek(Carbon::MONDAY);

        return [$senin->toDateString(), $senin->copy()->addDays(5)->toDateString()];
    }


    private function idTahunAjaranAktif(): ?int
    {


        $id = DB::table('tahun_ajaran')
            ->where('is_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_tahun_ajaran')
            ->value('id_tahun_ajaran');

        return $id === null ? null : (int) $id;
    }


    private function batasiTahunAjaranAktif($q, ?int $idTahunAjaran)
    {
        return $idTahunAjaran === null ? $q : $q->where('k.id_tahun_ajaran', $idTahunAjaran);
    }


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
