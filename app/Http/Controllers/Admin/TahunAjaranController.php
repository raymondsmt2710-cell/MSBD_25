<?php

namespace App\Http\Controllers\Admin;

use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TahunAjaranController extends AdminController
{
    public function index()
    {
        return $this->halaman();
    }


    private function halaman(?TahunAjaran $edit = null)
    {
        $tahunAjaran = DB::table('tahun_ajaran as t')
            ->select('t.*')
            ->selectSub(DB::table('kelas')->selectRaw('COUNT(*)')->whereColumn('id_tahun_ajaran', 't.id_tahun_ajaran'), 'jumlah_kelas')
            ->selectSub(DB::table('pemilihan')->selectRaw('COUNT(*)')->whereColumn('id_tahun_ajaran', 't.id_tahun_ajaran'), 'jumlah_pemilihan')
            ->orderByDesc('t.tanggal_mulai')
            ->get();

        return view('admin.tahun-ajaran.index', [
            'tahunAjaran' => $tahunAjaran,
            'edit' => $edit ?? new TahunAjaran,
        ]);
    }

    public function edit(TahunAjaran $tahunAjaran)
    {
        return $this->halaman($tahunAjaran);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $ta = TahunAjaran::create($data + ['is_aktif' => false]);


        if (! TahunAjaran::where('is_aktif', true)->exists()) {
            $this->tandaiAktif($ta);
        }

        return redirect()->route('admin.tahun-ajaran.index')->with('success', "Tahun ajaran {$ta->nama} dibuat.");
    }

    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        $tahunAjaran->update($this->validasi($request, $tahunAjaran));

        return redirect()->route('admin.tahun-ajaran.index')->with('success', "Tahun ajaran {$tahunAjaran->nama} diperbarui.");
    }

    public function aktifkan(TahunAjaran $tahunAjaran)
    {
        $this->tandaiAktif($tahunAjaran);

        return back()->with('success', "{$tahunAjaran->nama} sekarang menjadi tahun ajaran aktif.");
    }

    public function destroy(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->is_aktif) {
            return back()->with('error', 'Tahun ajaran aktif tidak dapat dihapus. Aktifkan tahun ajaran lain terlebih dahulu.');
        }

        return $this->hapusAman($tahunAjaran, "Tahun ajaran {$tahunAjaran->nama}",
            'masih dipakai oleh kelas, pemilihan, atau kepengurusan organisasi.');
    }

    private function tandaiAktif(TahunAjaran $ta): void
    {
        DB::transaction(function () use ($ta) {
            TahunAjaran::where('is_aktif', true)->lockForUpdate()->update(['is_aktif' => false]);
            $ta->update(['is_aktif' => true]);
        });
    }

    private function validasi(Request $request, ?TahunAjaran $ta = null): array
    {
        $data = $request->validate([
            'nama' => [
                'required', 'regex:/^\d{4}\/\d{4}$/',
                Rule::unique('tahun_ajaran', 'nama')->ignore($ta?->id_tahun_ajaran, 'id_tahun_ajaran'),
                function ($attr, $nilai, $gagal) {
                    [$awal, $akhir] = array_map('intval', explode('/', $nilai) + [1 => 0]);
                    if ($akhir !== $awal + 1) {
                        $gagal('Format tahun ajaran harus berurutan, misalnya 2026/2027.');
                    }
                },
            ],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
        ], ['nama.regex' => 'Format nama tahun ajaran: YYYY/YYYY, misalnya 2026/2027.']);


        $bentrok = TahunAjaran::query()
            ->when($ta, fn ($q) => $q->where('id_tahun_ajaran', '!=', $ta->id_tahun_ajaran))
            ->where('tanggal_mulai', '<=', $data['tanggal_selesai'])
            ->where('tanggal_selesai', '>=', $data['tanggal_mulai'])
            ->first();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => "Periode bertumpang-tindih dengan tahun ajaran {$bentrok->nama}.",
            ]);
        }

        return $data;
    }
}
