<?php

namespace App\Http\Controllers\Admin;

use App\Models\Jabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Fitur 7 — Kelola master jabatan.
 *
 * `lingkup` menentukan apakah jabatan dipakai di pengurus kelas atau pengurus organisasi;
 * `kuota` membatasi jumlah pemegang jabatan per kelas / per organisasi per tahun ajaran
 * dan diperiksa saat penetapan pengurus (fitur 12).
 */
class JabatanController extends AdminController
{
    public function index()
    {
        return $this->halaman();
    }

    /** Daftar dan form berbagi satu halaman: form berisi baris yang sedang diubah, atau kosong. */
    private function halaman(?Jabatan $edit = null)
    {
        $jabatan = DB::table('jabatan as j')
            ->select('j.*')
            ->selectRaw('(SELECT COUNT(*) FROM pengurus_kelas pk WHERE pk.id_jabatan = j.id_jabatan)
                       + (SELECT COUNT(*) FROM pengurus_organisasi po WHERE po.id_jabatan = j.id_jabatan) AS dipakai')
            ->orderBy('j.lingkup')
            ->orderBy('j.urutan')
            ->orderBy('j.nama_jabatan')
            ->get();

        return view('admin.jabatan.index', ['jabatan' => $jabatan, 'edit' => $edit ?? new Jabatan(['kuota' => 1, 'urutan' => 0])]);
    }

    public function edit(Jabatan $jabatan)
    {
        return $this->halaman($jabatan);
    }

    public function store(Request $request)
    {
        $j = Jabatan::create($this->validasi($request));

        return redirect()->route('admin.jabatan.index')->with('success', "Jabatan {$j->nama_jabatan} ditambahkan.");
    }

    public function update(Request $request, Jabatan $jabatan)
    {
        $data = $this->validasi($request, $jabatan);

        if ($data['lingkup'] !== $jabatan->lingkup && $this->dipakai($jabatan)) {
            return back()->withInput()->with('error', 'Lingkup tidak dapat diubah karena jabatan ini sudah dipakai pengurus.');
        }

        $jabatan->update($data);

        return redirect()->route('admin.jabatan.index')->with('success', "Jabatan {$jabatan->nama_jabatan} diperbarui.");
    }

    public function destroy(Jabatan $jabatan)
    {
        return $this->hapusAman($jabatan, "Jabatan {$jabatan->nama_jabatan}", 'masih dipegang oleh pengurus kelas atau organisasi.');
    }

    private function dipakai(Jabatan $j): bool
    {
        return DB::table('pengurus_kelas')->where('id_jabatan', $j->id_jabatan)->exists()
            || DB::table('pengurus_organisasi')->where('id_jabatan', $j->id_jabatan)->exists();
    }

    private function validasi(Request $request, ?Jabatan $j = null): array
    {
        return $request->validate([
            'nama_jabatan' => ['required', 'string', 'max:50',
                Rule::unique('jabatan', 'nama_jabatan')
                    ->where('lingkup', $request->input('lingkup'))
                    ->ignore($j?->id_jabatan, 'id_jabatan')],
            'lingkup' => ['required', Rule::in(['kelas', 'organisasi'])],
            'kuota' => ['required', 'integer', 'min:1', 'max:50'],
            'urutan' => ['required', 'integer', 'min:0', 'max:255'],
        ], ['nama_jabatan.unique' => 'Jabatan dengan nama dan lingkup yang sama sudah ada.']);
    }
}
