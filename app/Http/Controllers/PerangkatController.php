<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerangkatController extends Controller
{

    public function index()
    {
        $this->authorize('perangkat.lihat');

        $kelas = DB::table('kelas as k')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'k.id_wali_kelas')
            ->leftJoin('tahun_ajaran as t', 't.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->orderBy('k.tingkat')->orderBy('k.nama_kelas')
            ->select(
                'k.id_kelas', 'k.nama_kelas', 'k.tingkat', 'k.jurusan', 'k.ruangan',
                'g.nama_guru as wali_kelas',
                't.nama as tahun_ajaran',
                DB::raw('(SELECT COUNT(*) FROM pengurus_kelas pk WHERE pk.id_kelas = k.id_kelas) as jumlah_pengurus'),
                DB::raw('(SELECT COUNT(*) FROM anggota_kelas ak WHERE ak.id_kelas = k.id_kelas) as jumlah_anggota')
            )
            ->get();

        $organisasi = DB::table('organisasi as o')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'o.id_pembina')
            ->orderBy('o.nama_organisasi')
            ->select(
                'o.id_organisasi', 'o.nama_organisasi', 'o.singkatan', 'o.deskripsi', 'o.logo_url',
                'g.nama_guru as pembina',
                DB::raw('(SELECT COUNT(*) FROM pengurus_organisasi po WHERE po.id_organisasi = o.id_organisasi) as jumlah_pengurus')
            )
            ->get();

        return view('perangkat.index', compact('kelas', 'organisasi'));
    }


    public function kelas(int $id)
    {
        $this->authorize('perangkat.lihat');

        $kelas = DB::table('kelas as k')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'k.id_wali_kelas')
            ->leftJoin('tahun_ajaran as t', 't.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->where('k.id_kelas', $id)
            ->select('k.*', 'g.nama_guru as wali_kelas', 't.nama as tahun_ajaran')
            ->first() ?? abort(404);


        $jabatan = DB::table('jabatan')
            ->where('lingkup', 'kelas')
            ->orderBy('urutan')->orderBy('id_jabatan')
            ->get();

        $pengurus = DB::table('v_pengurus_lengkap')
            ->where('id_kelas', $id)
            ->orderBy('urutan')
            ->get()
            ->groupBy('id_jabatan');

        $jumlahAnggota = DB::table('anggota_kelas')->where('id_kelas', $id)->count();

        return view('perangkat.kelas', compact('kelas', 'jabatan', 'pengurus', 'jumlahAnggota'));
    }


    public function organisasi(Request $request, int $id)
    {
        $this->authorize('perangkat.lihat');

        $organisasi = DB::table('organisasi as o')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'o.id_pembina')
            ->where('o.id_organisasi', $id)
            ->select('o.*', 'g.nama_guru as pembina')
            ->first() ?? abort(404);


        $daftarTahun = DB::table('tahun_ajaran')
            ->orderByDesc('nama')
            ->get();

        $idTahun = (int) ($request->input('tahun')
            ?? DB::table('tahun_ajaran')->where('is_aktif', true)->value('id_tahun_ajaran')
            ?? DB::table('tahun_ajaran')->orderByDesc('nama')->value('id_tahun_ajaran')
            ?? 0);

        $jabatan = DB::table('jabatan')
            ->where('lingkup', 'organisasi')
            ->orderBy('urutan')->orderBy('id_jabatan')
            ->get();

        $pengurus = DB::table('v_pengurus_organisasi_lengkap')
            ->where('id_organisasi', $id)
            ->where('id_tahun_ajaran', $idTahun)
            ->orderBy('urutan')
            ->get()
            ->groupBy('id_jabatan');

        return view('perangkat.organisasi', compact(
            'organisasi', 'jabatan', 'pengurus', 'daftarTahun', 'idTahun'
        ));
    }
}
