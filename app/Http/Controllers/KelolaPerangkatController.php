<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KelolaPerangkatController extends Controller
{

    public function index()
    {
        $this->authorize('perangkat.kelola');

        $policy = new \App\Policies\PerangkatPolicy;
        $user = Auth::user();



        $peran = $user->role instanceof \App\Enums\Role ? $user->role->value : (string) $user->role;
        $bukanAdmin = $peran !== \App\Enums\Role::Admin->value;

        $idKelas = $policy->kelasYangBoleh($user);
        $idOrganisasi = $policy->organisasiYangBoleh($user);

        $kelas = DB::table('kelas as k')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'k.id_wali_kelas')
            ->when($bukanAdmin, fn ($q) => $q->whereIn('k.id_kelas', $idKelas))
            ->orderBy('k.tingkat')->orderBy('k.nama_kelas')
            ->select('k.id_kelas', 'k.nama_kelas', 'k.tingkat', 'g.nama_guru as wali_kelas',
                DB::raw('(SELECT COUNT(*) FROM pengurus_kelas pk WHERE pk.id_kelas = k.id_kelas) as jumlah_pengurus'))
            ->get();

        $organisasi = DB::table('organisasi as o')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'o.id_pembina')
            ->when($bukanAdmin, fn ($q) => $q->whereIn('o.id_organisasi', $idOrganisasi))
            ->orderBy('o.nama_organisasi')
            ->select('o.id_organisasi', 'o.nama_organisasi', 'o.singkatan', 'g.nama_guru as pembina',
                DB::raw('(SELECT COUNT(*) FROM pengurus_organisasi po WHERE po.id_organisasi = o.id_organisasi) as jumlah_pengurus'))
            ->get();

        return view('perangkat.kelola.index', compact('kelas', 'organisasi'));
    }


    public function kelas(int $id)
    {
        $this->authorize('perangkat.kelolaKelas', $id);

        $kelas = DB::table('kelas as k')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'k.id_wali_kelas')
            ->where('k.id_kelas', $id)
            ->select('k.*', 'g.nama_guru as wali_kelas')
            ->first() ?? abort(404);

        $jabatan = DB::table('jabatan')->where('lingkup', 'kelas')
            ->orderBy('urutan')->orderBy('id_jabatan')->get();


        $siswa = DB::table('anggota_kelas as ak')
            ->join('siswa as s', 's.id_siswa', '=', 'ak.id_siswa')
            ->where('ak.id_kelas', $id)
            ->orderBy('ak.no_absen')->orderBy('s.nama_siswa')
            ->select('s.id_siswa', 's.nis', 's.nama_siswa', 'ak.no_absen')
            ->get();


        $terpilih = DB::table('pengurus_kelas')
            ->where('id_kelas', $id)
            ->pluck('id_siswa', 'id_jabatan')
            ->map(fn ($v) => (int) $v)
            ->all();

        return view('perangkat.kelola.kelas', compact('kelas', 'jabatan', 'siswa', 'terpilih'));
    }


    public function simpanKelas(Request $request, int $id)
    {
        $this->authorize('perangkat.kelolaKelas', $id);

        $data = $request->validate([
            'pengurus' => 'nullable|array',
            'pengurus.*' => 'nullable|integer|exists:siswa,id_siswa',
        ]);

        $pengurus = array_filter($data['pengurus'] ?? [], fn ($v) => $v !== null && $v !== '');


        $duplikat = array_diff_assoc($pengurus, array_unique($pengurus));
        if ($duplikat !== []) {
            return back()->withInput()->with('error',
                'Satu siswa tidak boleh memegang lebih dari satu jabatan di kelas ini.');
        }

        try {
            DB::beginTransaction();

            DB::table('pengurus_kelas')->where('id_kelas', $id)->delete();

            foreach ($pengurus as $idJabatan => $idSiswa) {
                DB::table('pengurus_kelas')->insert([
                    'id_kelas' => $id,
                    'id_siswa' => (int) $idSiswa,
                    'id_jabatan' => (int) $idJabatan,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return back()->withInput()->with('error',
                'Gagal menyimpan pengurus: '.$e->getMessage());
        }

        return redirect()->route('perangkat.kelola.kelas', $id)
            ->with('success', 'Susunan pengurus berhasil disimpan.');
    }


    public function organisasi(Request $request, int $id)
    {
        $this->authorize('perangkat.kelolaOrganisasi', $id);

        $organisasi = DB::table('organisasi as o')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'o.id_pembina')
            ->where('o.id_organisasi', $id)
            ->select('o.*', 'g.nama_guru as pembina')
            ->first() ?? abort(404);

        $daftarTahun = DB::table('tahun_ajaran')->orderByDesc('nama')->get();

        $idTahun = (int) ($request->input('tahun')
            ?? DB::table('tahun_ajaran')->where('is_aktif', true)->value('id_tahun_ajaran')
            ?? DB::table('tahun_ajaran')->orderByDesc('nama')->value('id_tahun_ajaran')
            ?? 0);

        $jabatan = DB::table('jabatan')->where('lingkup', 'organisasi')
            ->orderBy('urutan')->orderBy('id_jabatan')->get();


        $siswa = DB::table('siswa')->where('status', 'aktif')
            ->orderBy('nama_siswa')
            ->select('id_siswa', 'nis', 'nama_siswa')
            ->get();

        $terpilih = DB::table('pengurus_organisasi')
            ->where('id_organisasi', $id)->where('id_tahun_ajaran', $idTahun)
            ->pluck('id_siswa', 'id_jabatan')
            ->map(fn ($v) => (int) $v)
            ->all();

        $bidang = DB::table('pengurus_organisasi')
            ->where('id_organisasi', $id)->where('id_tahun_ajaran', $idTahun)
            ->pluck('bidang', 'id_jabatan')
            ->all();

        return view('perangkat.kelola.organisasi', compact(
            'organisasi', 'jabatan', 'siswa', 'terpilih', 'bidang', 'daftarTahun', 'idTahun'
        ));
    }


    public function simpanOrganisasi(Request $request, int $id)
    {
        $this->authorize('perangkat.kelolaOrganisasi', $id);

        $data = $request->validate([
            'id_tahun_ajaran' => 'required|integer|exists:tahun_ajaran,id_tahun_ajaran',
            'pengurus' => 'nullable|array',
            'pengurus.*' => 'nullable|integer|exists:siswa,id_siswa',
            'bidang' => 'nullable|array',
            'bidang.*' => 'nullable|string|max:50',
        ]);

        $idTahun = (int) $data['id_tahun_ajaran'];
        $pengurus = array_filter($data['pengurus'] ?? [], fn ($v) => $v !== null && $v !== '');

        $duplikat = array_diff_assoc($pengurus, array_unique($pengurus));
        if ($duplikat !== []) {
            return back()->withInput()->with('error',
                'Satu siswa tidak boleh memegang lebih dari satu jabatan di organisasi ini.');
        }

        try {
            DB::beginTransaction();

            DB::table('pengurus_organisasi')
                ->where('id_organisasi', $id)->where('id_tahun_ajaran', $idTahun)
                ->delete();

            foreach ($pengurus as $idJabatan => $idSiswa) {
                DB::table('pengurus_organisasi')->insert([
                    'id_organisasi' => $id,
                    'id_tahun_ajaran' => $idTahun,
                    'id_siswa' => (int) $idSiswa,
                    'id_jabatan' => (int) $idJabatan,
                    'bidang' => $data['bidang'][$idJabatan] ?? null,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return back()->withInput()->with('error',
                'Gagal menyimpan pengurus: '.$e->getMessage());
        }

        return redirect()->route('perangkat.kelola.organisasi', ['id' => $id, 'tahun' => $idTahun])
            ->with('success', 'Susunan pengurus berhasil disimpan.');
    }
}
