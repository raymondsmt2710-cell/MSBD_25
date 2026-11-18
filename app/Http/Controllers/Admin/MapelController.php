<?php

namespace App\Http\Controllers\Admin;

use App\Models\MataPelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Fitur 6 — Kelola mata pelajaran (dipakai jadwal pelajaran). */
class MapelController extends AdminController
{
    public function index()
    {
        return $this->halaman();
    }

    /** Daftar dan form berbagi satu halaman: form berisi baris yang sedang diubah, atau kosong. */
    private function halaman(?MataPelajaran $edit = null)
    {
        $mapel = DB::table('mata_pelajaran as m')
            ->select('m.*')
            ->selectSub(DB::table('jadwal_pelajaran')->selectRaw('COUNT(*)')->whereColumn('id_mapel', 'm.id_mapel'), 'jumlah_jadwal')
            ->orderBy('m.kode_mapel')
            ->get();

        return view('admin.mapel.index', ['mapel' => $mapel, 'edit' => $edit ?? new MataPelajaran]);
    }

    public function edit(MataPelajaran $mapel)
    {
        return $this->halaman($mapel);
    }

    public function store(Request $request)
    {
        $m = MataPelajaran::create($this->validasi($request));

        return redirect()->route('admin.mapel.index')->with('success', "Mapel {$m->nama_mapel} ditambahkan.");
    }

    public function update(Request $request, MataPelajaran $mapel)
    {
        $mapel->update($this->validasi($request, $mapel));

        return redirect()->route('admin.mapel.index')->with('success', "Mapel {$mapel->nama_mapel} diperbarui.");
    }

    public function destroy(MataPelajaran $mapel)
    {
        return $this->hapusAman($mapel, "Mapel {$mapel->nama_mapel}", 'masih dipakai di jadwal pelajaran.');
    }

    private function validasi(Request $request, ?MataPelajaran $m = null): array
    {
        // Kode disimpan huruf besar agar "mtk" dan "MTK" tidak dianggap berbeda.
        $request->merge(['kode_mapel' => strtoupper(trim((string) $request->input('kode_mapel')))]);

        return $request->validate([
            'kode_mapel' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9\-]+$/',
                Rule::unique('mata_pelajaran', 'kode_mapel')->ignore($m?->id_mapel, 'id_mapel')],
            'nama_mapel' => ['required', 'string', 'max:100'],
        ], ['kode_mapel.regex' => 'Kode mapel hanya huruf, angka, dan tanda hubung.']);
    }
}
