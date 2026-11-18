<?php

namespace App\Http\Controllers\Admin;

use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Fitur 18 — Lihat presensi semua kelas (hanya-baca).
 *
 * Admin memantau lintas kelas untuk evaluasi. Aksi operasional (membuka sesi,
 * koreksi manual, verifikasi izin) tetap milik guru karena kolom dibuka_oleh,
 * diubah_oleh, dan diverifikasi_oleh merujuk tabel guru — itu sebabnya controller
 * ini sengaja tidak punya satu pun aksi tulis.
 */
class PresensiController extends AdminController
{
    public function index(Request $request)
    {
        [$filter, $idTahun, $dari, $sampai] = $this->filter($request);

        $sesi = $this->querySesi($idTahun, $dari, $sampai, $filter['kelas'] ?? null)
            ->orderByDesc('sp.tanggal')
            ->orderByDesc('j.jam_mulai')
            ->paginate(30)
            ->withQueryString();

        // Rekap per kelas dalam rentang tanggal: persentase hadir dari presensi yang tercatat.
        $rekapKelas = DB::table('presensi as p')
            ->join('sesi_presensi as sp', 'sp.id_sesi', '=', 'p.id_sesi')
            ->join('jadwal_pelajaran as j', 'j.id_jadwal', '=', 'sp.id_jadwal')
            ->join('kelas as k', 'k.id_kelas', '=', 'j.id_kelas')
            ->where('k.id_tahun_ajaran', $idTahun ?? 0)
            ->whereBetween('sp.tanggal', [$dari, $sampai])
            ->groupBy('k.id_kelas', 'k.nama_kelas')
            ->orderBy('k.nama_kelas')
            ->select('k.id_kelas', 'k.nama_kelas')
            ->selectRaw("COUNT(DISTINCT sp.id_sesi) AS sesi,
                SUM(p.status = 'hadir') AS hadir, SUM(p.status = 'izin') AS izin,
                SUM(p.status = 'sakit') AS sakit, SUM(p.status = 'alpa') AS alpa, COUNT(*) AS total")
            ->get();

        return view('admin.presensi.index', [
            'sesi' => $sesi,
            'rekapKelas' => $rekapKelas,
            'daftarKelas' => DB::table('kelas')->where('id_tahun_ajaran', $idTahun ?? 0)->orderBy('nama_kelas')->get(['id_kelas', 'nama_kelas']),
            'tahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'idTahun' => $idTahun,
            'dari' => $dari,
            'sampai' => $sampai,
        ]);
    }

    public function sesi(int $sesi)
    {
        $info = $this->querySesi(null, null, null, null)->where('sp.id_sesi', $sesi)->first();
        abort_if($info === null, 404);

        // Seluruh roster kelas, termasuk siswa yang belum punya baris presensi pada sesi ini.
        $siswa = DB::table('anggota_kelas as ak')
            ->join('siswa as s', 's.id_siswa', '=', 'ak.id_siswa')
            ->leftJoin('presensi as p', fn ($j) => $j->on('p.id_siswa', '=', 's.id_siswa')->where('p.id_sesi', $sesi))
            ->leftJoin('guru as g', 'g.id_guru', '=', 'p.diubah_oleh')
            ->where('ak.id_kelas', $info->id_kelas)
            ->orderByRaw('ak.no_absen IS NULL, ak.no_absen')
            ->orderBy('s.nama_siswa')
            ->get(['ak.no_absen', 's.nis', 's.nama_siswa', 'p.status', 'p.metode', 'p.waktu_presensi', 'p.catatan', 'g.nama_guru as diubah_oleh']);

        return view('admin.presensi.sesi', compact('info', 'siswa'));
    }

    public function izin(Request $request)
    {
        [$filter, $idTahun, $dari, $sampai] = $this->filter($request, ['status' => ['nullable', Rule::in(['menunggu', 'disetujui', 'ditolak'])]]);

        $izin = DB::table('pengajuan_izin as pi')
            ->join('siswa as s', 's.id_siswa', '=', 'pi.id_siswa')
            ->leftJoin('anggota_kelas as ak', 'ak.id_siswa', '=', 's.id_siswa')
            ->leftJoin('kelas as k', fn ($j) => $j->on('k.id_kelas', '=', 'ak.id_kelas')->where('k.id_tahun_ajaran', $idTahun ?? 0))
            ->leftJoin('guru as g', 'g.id_guru', '=', 'pi.diverifikasi_oleh')
            // Pengajuan yang rentangnya beririsan dengan rentang filter.
            ->where('pi.tanggal_mulai', '<=', $sampai)
            ->where('pi.tanggal_selesai', '>=', $dari)
            ->whereNotNull('k.id_kelas')
            ->when($filter['kelas'] ?? null, fn ($q, $k) => $q->where('k.id_kelas', $k))
            ->when($filter['status'] ?? null, fn ($q, $st) => $q->where('pi.status', $st))
            ->orderByRaw("FIELD(pi.status, 'menunggu', 'disetujui', 'ditolak')")
            ->orderByDesc('pi.diajukan_pada')
            ->select('pi.*', 's.nama_siswa', 's.nis', 'k.nama_kelas', 'g.nama_guru as verifikator')
            ->paginate(30)
            ->withQueryString();

        return view('admin.presensi.izin', [
            'izin' => $izin,
            'daftarKelas' => DB::table('kelas')->where('id_tahun_ajaran', $idTahun ?? 0)->orderBy('nama_kelas')->get(['id_kelas', 'nama_kelas']),
            'tahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'idTahun' => $idTahun,
            'dari' => $dari,
            'sampai' => $sampai,
        ]);
    }

    /** Sesi presensi beserta jumlah per status. Semua parameter null = tanpa filter. */
    private function querySesi(?int $idTahun, ?string $dari, ?string $sampai, ?int $idKelas)
    {
        return DB::table('sesi_presensi as sp')
            ->join('jadwal_pelajaran as j', 'j.id_jadwal', '=', 'sp.id_jadwal')
            ->join('kelas as k', 'k.id_kelas', '=', 'j.id_kelas')
            ->join('mata_pelajaran as m', 'm.id_mapel', '=', 'j.id_mapel')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'sp.dibuka_oleh')
            ->leftJoin('presensi as p', 'p.id_sesi', '=', 'sp.id_sesi')
            ->when($idTahun !== null, fn ($q) => $q->where('k.id_tahun_ajaran', $idTahun))
            ->when($dari !== null, fn ($q) => $q->whereBetween('sp.tanggal', [$dari, $sampai]))
            ->when($idKelas, fn ($q) => $q->where('k.id_kelas', $idKelas))
            ->groupBy('sp.id_sesi', 'sp.tanggal', 'sp.status', 'sp.dibuka_pada', 'sp.ditutup_pada',
                'j.jam_mulai', 'j.jam_selesai', 'k.id_kelas', 'k.nama_kelas', 'm.nama_mapel', 'g.nama_guru')
            ->select('sp.id_sesi', 'sp.tanggal', 'sp.status', 'sp.dibuka_pada', 'sp.ditutup_pada',
                'j.jam_mulai', 'j.jam_selesai', 'k.id_kelas', 'k.nama_kelas', 'm.nama_mapel', 'g.nama_guru')
            ->selectRaw("SUM(p.status = 'hadir') AS hadir, SUM(p.status = 'izin') AS izin,
                SUM(p.status = 'sakit') AS sakit, SUM(p.status = 'alpa') AS alpa")
            ->selectSub(DB::table('anggota_kelas')->selectRaw('COUNT(*)')->whereColumn('id_kelas', 'k.id_kelas'), 'jumlah_siswa');
    }

    /** @return array{0: array, 1: ?int, 2: string, 3: string} */
    private function filter(Request $request, array $tambahan = []): array
    {
        $filter = $request->validate($tambahan + [
            'kelas' => ['nullable', 'integer'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $idTahun = $this->idTahunAjaranTerpilih($request->query('tahun'));
        $sampai = Carbon::parse($filter['sampai'] ?? today())->toDateString();
        $dari = Carbon::parse($filter['dari'] ?? Carbon::parse($sampai)->subDays(6))->toDateString();

        return [$filter, $idTahun, $dari, $sampai];
    }
}
