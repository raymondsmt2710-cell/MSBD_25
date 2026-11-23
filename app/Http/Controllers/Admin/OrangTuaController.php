<?php

namespace App\Http\Controllers\Admin;

use App\Models\OrangTua;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrangTuaController extends AdminController
{
    private const HUBUNGAN = ['ayah', 'ibu', 'wali'];

    public function index(Request $request)
    {
        $cari = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;

        $orangTua = DB::table('orang_tua as o')
            ->join('users as u', 'u.id_user', '=', 'o.id_user')
            ->leftJoin('orang_tua_siswa as os', 'os.id_orang_tua', '=', 'o.id_orang_tua')
            ->leftJoin('siswa as s', 's.id_siswa', '=', 'os.id_siswa')
            ->select('o.id_orang_tua', 'o.nama', 'o.no_hp', 'o.email', 'u.username', 'u.is_active')
            ->selectRaw("GROUP_CONCAT(CONCAT(s.nama_siswa, ' (', os.hubungan, ')') ORDER BY s.nama_siswa SEPARATOR ', ') AS anak")



            ->selectRaw("(SUM(os.hubungan = 'ayah') > 0 AND SUM(os.hubungan = 'ibu') > 0) AS peran_ganda")
            ->when($cari, fn ($q) => $q->where(fn ($w) => $w->where('o.nama', 'like', "%{$cari}%")
                ->orWhere('s.nama_siswa', 'like', "%{$cari}%")
                ->orWhere('s.nis', 'like', "%{$cari}%")))
            ->groupBy('o.id_orang_tua', 'o.nama', 'o.no_hp', 'o.email', 'u.username', 'u.is_active')
            ->orderBy('o.nama')
            ->paginate(25)
            ->withQueryString();

        return view('admin.orang-tua.index', compact('orangTua'));
    }

    public function create(Request $request)
    {
        return view('admin.orang-tua.form', [
            'orangTua' => new OrangTua(['id_user' => $request->integer('id_user') ?: null]),
            'akun' => $this->akunTanpaProfil('orang_tua', 'orang_tua'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['id_user'] = $request->validate(['id_user' => $this->aturanIdUserProfil('orang_tua', 'orang_tua')])['id_user'];

        $orangTua = OrangTua::create($data);


        return redirect()->route('admin.orang-tua.show', $orangTua->id_orang_tua)
            ->with('success', 'Profil dibuat. Sekarang hubungkan ke siswa.');
    }

    public function show(OrangTua $orangTua)
    {
        $anak = DB::table('orang_tua_siswa as os')
            ->join('siswa as s', 's.id_siswa', '=', 'os.id_siswa')
            ->where('os.id_orang_tua', $orangTua->id_orang_tua)
            ->orderBy('s.nama_siswa')
            ->get(['s.id_siswa', 's.nis', 's.nama_siswa', 's.status', 'os.hubungan', 'os.is_kontak_utama']);

        $siswa = DB::table('siswa')
            ->whereNotIn('id_siswa', $anak->pluck('id_siswa'))
            ->where('status', 'aktif')
            ->orderBy('nama_siswa')
            ->get(['id_siswa', 'nis', 'nama_siswa']);

        $username = DB::table('users')->where('id_user', $orangTua->id_user)->value('username');




        $peran = $anak->pluck('hubungan')->unique();
        $peranGanda = $peran->contains('ayah') && $peran->contains('ibu');

        return view('admin.orang-tua.show', compact('orangTua', 'anak', 'siswa', 'username', 'peranGanda') + ['hubunganList' => self::HUBUNGAN]);
    }

    public function edit(OrangTua $orangTua)
    {
        $username = DB::table('users')->where('id_user', $orangTua->id_user)->value('username');

        return view('admin.orang-tua.form', compact('orangTua', 'username'));
    }

    public function update(Request $request, OrangTua $orangTua)
    {
        $orangTua->update($this->validasi($request));

        return redirect()->route('admin.orang-tua.show', $orangTua->id_orang_tua)->with('success', 'Profil diperbarui.');
    }

    public function hubungkan(Request $request, OrangTua $orangTua)
    {
        $data = $request->validate([
            'id_siswa' => [
                'required', 'integer', 'exists:siswa,id_siswa',
                Rule::unique('orang_tua_siswa', 'id_siswa')->where('id_orang_tua', $orangTua->id_orang_tua),
            ],
            'hubungan' => ['required', Rule::in(self::HUBUNGAN)],
            'is_kontak_utama' => ['sometimes', 'boolean'],
        ], ['id_siswa.unique' => 'Siswa ini sudah terhubung dengan orang tua tersebut.']);

        if ($pesan = $this->bentrokHubungan((int) $data['id_siswa'], $data['hubungan'], $orangTua->id_orang_tua)) {
            return back()->withInput()->with('error', $pesan);
        }

        DB::transaction(function () use ($orangTua, $data, $request) {
            $utama = $request->boolean('is_kontak_utama')

                || ! DB::table('orang_tua_siswa')->where('id_siswa', $data['id_siswa'])->where('is_kontak_utama', true)->exists();

            if ($utama) {
                $this->lepasKontakUtama((int) $data['id_siswa']);
            }

            DB::table('orang_tua_siswa')->insert([
                'id_orang_tua' => $orangTua->id_orang_tua,
                'id_siswa' => $data['id_siswa'],
                'hubungan' => $data['hubungan'],
                'is_kontak_utama' => $utama,
            ]);
        });

        return back()->with('success', 'Siswa berhasil dihubungkan.');
    }

    public function ubahHubungan(Request $request, OrangTua $orangTua, Siswa $siswa)
    {
        $data = $request->validate([
            'hubungan' => ['required', Rule::in(self::HUBUNGAN)],
            'is_kontak_utama' => ['sometimes', 'boolean'],
        ]);

        $relasi = $this->relasi($orangTua, $siswa);
        abort_unless($relasi->exists(), 404);

        if ($pesan = $this->bentrokHubungan($siswa->id_siswa, $data['hubungan'], $orangTua->id_orang_tua)) {
            return back()->with('error', $pesan);
        }

        DB::transaction(function () use ($relasi, $siswa, $data, $request) {
            if ($request->boolean('is_kontak_utama')) {
                $this->lepasKontakUtama($siswa->id_siswa);
            }

            $relasi->update([
                'hubungan' => $data['hubungan'],
                'is_kontak_utama' => $request->boolean('is_kontak_utama'),
            ]);
        });

        return back()->with('success', 'Relasi diperbarui.');
    }

    public function putuskan(OrangTua $orangTua, Siswa $siswa)
    {
        $terhapus = $this->relasi($orangTua, $siswa)->delete();
        abort_unless($terhapus > 0, 404);

        return back()->with('success', "{$siswa->nama_siswa} tidak lagi terhubung; akun orang tua ini tidak bisa memantaunya.");
    }

    private function relasi(OrangTua $orangTua, Siswa $siswa)
    {
        return DB::table('orang_tua_siswa')
            ->where('id_orang_tua', $orangTua->id_orang_tua)
            ->where('id_siswa', $siswa->id_siswa);
    }

    private function lepasKontakUtama(int $idSiswa): void
    {
        DB::table('orang_tua_siswa')->where('id_siswa', $idSiswa)->update(['is_kontak_utama' => false]);
    }



    private function bentrokHubungan(int $idSiswa, string $hubungan, int $abaikanIdOrangTua): ?string
    {
        if ($hubungan === 'wali') {
            return null;
        }


        $sudahAda = DB::table('orang_tua_siswa')
            ->where('id_siswa', $idSiswa)
            ->where('hubungan', $hubungan)
            ->where('id_orang_tua', '!=', $abaikanIdOrangTua)
            ->exists();

        if ($sudahAda) {
            return "Siswa ini sudah memiliki {$hubungan} yang terdaftar. Gunakan hubungan \"wali\" atau ubah data yang ada.";
        }



        $peranLain = DB::table('orang_tua_siswa')
            ->where('id_orang_tua', $abaikanIdOrangTua)
            ->where('hubungan', '!=', $hubungan)
            ->where('hubungan', '!=', 'wali')
            ->value('hubungan');

        if ($peranLain !== null) {
            return "Akun ini sudah terdaftar sebagai {$peranLain} pada anak lain. "
                .'Satu akun orang tua hanya boleh punya satu peran; '
                .'gunakan hubungan "wali" bila memang berbeda.';
        }

        return null;
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:15', 'regex:/^\+?[0-9]{8,14}$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'pekerjaan' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ], ['no_hp.regex' => 'Nomor HP hanya angka (boleh diawali +), 8–14 digit.']);
    }
}
