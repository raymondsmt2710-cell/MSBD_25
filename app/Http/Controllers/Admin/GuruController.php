<?php

namespace App\Http\Controllers\Admin;

use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Fitur 3 — Kelola data guru.
 *
 * Profil guru dibutuhkan agar guru bisa ditunjuk sebagai wali kelas, pengajar
 * di jadwal, atau pembina organisasi. Guru tidak dihapus (jadwal_pelajaran merujuknya
 * dengan RESTRICT); menonaktifkan akunnya sudah cukup untuk mencabut akses.
 */
class GuruController extends AdminController
{
    public function index(Request $request)
    {
        $cari = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;

        $guru = DB::table('guru as g')
            ->join('users as u', 'u.id_user', '=', 'g.id_user')
            ->select('g.*', 'u.username', 'u.is_active')
            // Ringkasan penugasan: berguna sebelum menonaktifkan seorang guru.
            ->selectSub(DB::table('kelas')->selectRaw("GROUP_CONCAT(nama_kelas SEPARATOR ', ')")
                ->whereColumn('id_wali_kelas', 'g.id_guru')
                ->whereIn('id_tahun_ajaran', DB::table('tahun_ajaran')->select('id_tahun_ajaran')->where('is_aktif', true)), 'wali_dari')
            ->selectSub(DB::table('jadwal_pelajaran')->selectRaw('COUNT(*)')->whereColumn('id_guru', 'g.id_guru'), 'jumlah_jadwal')
            ->selectSub(DB::table('organisasi')->selectRaw("GROUP_CONCAT(COALESCE(singkatan, nama_organisasi) SEPARATOR ', ')")
                ->whereColumn('id_pembina', 'g.id_guru'), 'pembina_dari')
            ->when($cari, fn ($q) => $q->where(fn ($w) => $w->where('g.nama_guru', 'like', "%{$cari}%")->orWhere('g.nip', 'like', "%{$cari}%")))
            ->orderBy('g.nama_guru')
            ->paginate(25)
            ->withQueryString();

        return view('admin.guru.index', compact('guru'));
    }

    public function create(Request $request)
    {
        return view('admin.guru.form', [
            'guru' => new Guru(['id_user' => $request->integer('id_user') ?: null]),
            'akun' => $this->akunTanpaProfil('guru', 'guru'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['id_user'] = $request->validate(['id_user' => $this->aturanIdUserProfil('guru', 'guru')])['id_user'];

        Guru::create($data);

        return redirect()->route('admin.guru.index')->with('success', "Guru {$data['nama_guru']} ditambahkan.");
    }

    public function edit(Guru $guru)
    {
        $username = DB::table('users')->where('id_user', $guru->id_user)->value('username');

        return view('admin.guru.form', compact('guru', 'username'));
    }

    public function update(Request $request, Guru $guru)
    {
        $guru->update($this->validasi($request, $guru));

        return redirect()->route('admin.guru.index')->with('success', "Data {$guru->nama_guru} diperbarui.");
    }

    private function validasi(Request $request, ?Guru $guru = null): array
    {
        return $request->validate([
            // NIP PNS 18 digit; honorer kadang memakai NUPTK/nomor lain, jadi cukup angka ≤ 20.
            'nip' => ['required', 'regex:/^[0-9]{1,20}$/', Rule::unique('guru', 'nip')->ignore($guru?->id_guru, 'id_guru')],
            'nama_guru' => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'no_hp' => ['nullable', 'string', 'max:15', 'regex:/^\+?[0-9]{8,14}$/'],
            'email' => ['nullable', 'email', 'max:100'],
        ], [
            'nip.regex' => 'NIP hanya boleh angka, maksimal 20 digit.',
            'no_hp.regex' => 'Nomor HP hanya angka (boleh diawali +), 8–14 digit.',
        ]);
    }
}
