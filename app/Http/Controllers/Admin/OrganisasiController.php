<?php

namespace App\Http\Controllers\Admin;

use App\Models\Organisasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrganisasiController extends AdminController
{
    public function index()
    {
        $idTahun = $this->idTahunAjaranTerpilih(null);

        $organisasi = DB::table('organisasi as o')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'o.id_pembina')
            ->select('o.*', 'g.nama_guru as pembina')
            ->selectSub(DB::table('pengurus_organisasi')->selectRaw('COUNT(*)')
                ->whereColumn('id_organisasi', 'o.id_organisasi')->where('id_tahun_ajaran', $idTahun ?? 0), 'jumlah_pengurus')
            ->selectSub(DB::table('pemilihan')->selectRaw('COUNT(*)')->whereColumn('id_organisasi', 'o.id_organisasi'), 'jumlah_pemilihan')
            ->orderBy('o.nama_organisasi')
            ->get();

        return view('admin.organisasi.index', compact('organisasi'));
    }

    public function create()
    {
        return view('admin.organisasi.form', ['organisasi' => new Organisasi, 'guru' => $this->daftarGuru()]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['logo_url'] = $request->hasFile('logo') ? $request->file('logo')->store('organisasi', 'public') : null;

        $o = Organisasi::create($data);

        return redirect()->route('admin.organisasi.index')->with('success', "Organisasi {$o->nama_organisasi} dibuat.");
    }

    public function edit(Organisasi $organisasi)
    {
        return view('admin.organisasi.form', ['organisasi' => $organisasi, 'guru' => $this->daftarGuru()]);
    }

    public function update(Request $request, Organisasi $organisasi)
    {
        $data = $this->validasi($request, $organisasi);

        if ($request->hasFile('logo')) {
            $lama = $organisasi->logo_url;
            $data['logo_url'] = $request->file('logo')->store('organisasi', 'public');
            if ($lama && ! str_starts_with($lama, 'http')) {
                Storage::disk('public')->delete($lama);
            }
        } elseif ($request->boolean('hapus_logo')) {
            if ($organisasi->logo_url && ! str_starts_with($organisasi->logo_url, 'http')) {
                Storage::disk('public')->delete($organisasi->logo_url);
            }
            $data['logo_url'] = null;
        }

        $organisasi->update($data);

        return redirect()->route('admin.organisasi.index')->with('success', "Organisasi {$organisasi->nama_organisasi} diperbarui.");
    }

    public function destroy(Organisasi $organisasi)
    {


        $dipakai = DB::table('pemilihan')->where('id_organisasi', $organisasi->id_organisasi)->exists()
            || DB::table('pengurus_organisasi')->where('id_organisasi', $organisasi->id_organisasi)->exists();

        if ($dipakai) {
            return back()->with('error', 'Organisasi tidak dapat dihapus karena sudah punya pengurus atau riwayat pemilihan.');
        }

        if ($organisasi->logo_url && ! str_starts_with($organisasi->logo_url, 'http')) {
            Storage::disk('public')->delete($organisasi->logo_url);
        }
        $organisasi->delete();

        return redirect()->route('admin.organisasi.index')->with('success', "Organisasi {$organisasi->nama_organisasi} dihapus.");
    }

    private function daftarGuru()
    {
        return DB::table('guru as g')->join('users as u', 'u.id_user', '=', 'g.id_user')
            ->where('u.is_active', true)->orderBy('g.nama_guru')->get(['g.id_guru', 'g.nama_guru']);
    }

    private function validasi(Request $request, ?Organisasi $o = null): array
    {
        $data = $request->validate([
            'nama_organisasi' => ['required', 'string', 'max:100',
                Rule::unique('organisasi', 'nama_organisasi')->ignore($o?->id_organisasi, 'id_organisasi')],
            'singkatan' => ['nullable', 'string', 'max:20'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'id_pembina' => ['nullable', 'integer', 'exists:guru,id_guru'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'hapus_logo' => ['sometimes', 'boolean'],
        ]);

        unset($data['logo'], $data['hapus_logo']);

        return $data;
    }
}
