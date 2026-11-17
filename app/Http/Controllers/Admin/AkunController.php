<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Fitur 1 — Kelola akun pengguna.
 *
 * Ini satu-satunya jalur pembuatan akun. Akun sengaja TIDAK bisa dihapus:
 * users dirujuk oleh transaksi_kas, denah_kelas, dan pemilihan dengan ON DELETE RESTRICT,
 * sedangkan profil siswa/guru ikut terhapus (CASCADE) beserta riwayat presensinya.
 * Mencabut akses cukup dengan menonaktifkan akun (is_active = 0); middleware
 * PastikanAkunAktif langsung memutus session yang masih terbuka.
 */
class AkunController extends AdminController
{
    public const ROLE = ['admin', 'guru', 'siswa', 'orang_tua'];

    /** Role yang wajib punya baris profil, beserta rute pembuatan profilnya. */
    private const RUTE_PROFIL = [
        'siswa' => 'admin.siswa.create',
        'guru' => 'admin.guru.create',
        'orang_tua' => 'admin.orang-tua.create',
    ];

    public function index(Request $request)
    {
        $filter = $request->validate([
            'role' => ['nullable', Rule::in(self::ROLE)],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
            'q' => ['nullable', 'string', 'max:50'],
        ]);

        $akun = DB::table('users as u')
            ->leftJoin('siswa as s', 's.id_user', '=', 'u.id_user')
            ->leftJoin('guru as g', 'g.id_user', '=', 'u.id_user')
            ->leftJoin('orang_tua as o', 'o.id_user', '=', 'u.id_user')
            ->select('u.id_user', 'u.username', 'u.role', 'u.is_active', 'u.last_login', 'u.created_at')
            ->selectRaw('COALESCE(s.nama_siswa, g.nama_guru, o.nama) AS nama_profil')
            ->when($filter['role'] ?? null, fn ($q, $r) => $q->where('u.role', $r))
            ->when(($filter['status'] ?? null) === 'aktif', fn ($q) => $q->where('u.is_active', true))
            ->when(($filter['status'] ?? null) === 'nonaktif', fn ($q) => $q->where('u.is_active', false))
            ->when($filter['q'] ?? null, function ($q, $cari) {
                $q->where(function ($w) use ($cari) {
                    $w->where('u.username', 'like', "%{$cari}%")
                        ->orWhere('s.nama_siswa', 'like', "%{$cari}%")
                        ->orWhere('g.nama_guru', 'like', "%{$cari}%")
                        ->orWhere('o.nama', 'like', "%{$cari}%");
                });
            })
            ->orderBy('u.role')
            ->orderBy('u.username')
            ->paginate(25)
            ->withQueryString();

        return view('admin.akun.index', ['akun' => $akun, 'roles' => self::ROLE]);
    }

    public function create()
    {
        return view('admin.akun.form', ['user' => new User(['is_active' => true]), 'roles' => self::ROLE]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'role' => ['required', Rule::in(self::ROLE)],
            'password' => ['nullable', 'string', Password::min(8)],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'username.regex' => 'Username hanya boleh huruf, angka, titik, garis bawah, dan tanda hubung.',
        ]);

        // Password kosong → dibuatkan otomatis dan ditampilkan SEKALI kepada admin.
        $sandiDiisi = $data['password'] ?? null;
        $sandi = $sandiDiisi ?: $this->sandiAcak();

        $user = User::create([
            'username' => $data['username'],
            'role' => $data['role'],
            'password_hash' => Hash::make($sandi),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $pesan = "Akun {$user->username} dibuat.";
        if (! $sandiDiisi) {
            session()->flash('sandi_baru', $sandi);
        }

        // Akun siswa/guru/orang tua belum bisa dipakai penuh tanpa profil: langsung arahkan ke form profil.
        $peran = $user->role?->value ?? $user->role;
        if ($rute = self::RUTE_PROFIL[$peran] ?? null) {
            return redirect()->route($rute, ['id_user' => $user->id_user])
                ->with('success', $pesan.' Lengkapi profilnya sekarang.');
        }

        return redirect()->route('admin.akun.index')->with('success', $pesan);
    }

    public function edit(User $user)
    {
        return view('admin.akun.form', [
            'user' => $user,
            'roles' => self::ROLE,
            'punyaProfil' => $this->punyaProfil($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username' => [
                'required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($user->id_user, 'id_user'),
            ],
            'role' => ['required', Rule::in(self::ROLE)],
        ]);

        $peranLama = $user->role?->value ?? $user->role;

        if ($data['role'] !== $peranLama) {
            if ($user->is($request->user())) {
                return back()->with('error', 'Anda tidak dapat mengubah peran akun Anda sendiri.');
            }

            // Profil (siswa/guru/orang_tua) terikat ke role lama. Mengganti role akan
            // membuat profil itu yatim dan akun tidak bisa dipakai di modul mana pun.
            if ($this->punyaProfil($user)) {
                return back()->with('error', 'Peran tidak dapat diubah karena akun ini sudah memiliki profil '
                    .str_replace('_', ' ', $peranLama).'. Buat akun baru untuk peran lain.');
            }

            if ($peranLama === 'admin' && $this->adminAktifLainnya($user) === 0) {
                return back()->with('error', 'Ini satu-satunya admin aktif. Peran tidak dapat diubah.');
            }
        }

        $user->update($data);

        return redirect()->route('admin.akun.index')->with('success', "Akun {$user->username} diperbarui.");
    }

    public function ubahStatus(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $peran = $user->role?->value ?? $user->role;

        if ($user->is_active && $peran === 'admin' && $this->adminAktifLainnya($user) === 0) {
            return back()->with('error', 'Ini satu-satunya admin aktif. Akun tidak dapat dinonaktifkan.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', "Akun {$user->username} ".($user->is_active ? 'diaktifkan.' : 'dinonaktifkan; session yang terbuka akan diputus.'));
    }

    public function resetPassword(User $user)
    {
        $sandi = $this->sandiAcak();
        $user->update(['password_hash' => Hash::make($sandi)]);

        return back()
            ->with('success', "Password {$user->username} direset.")
            ->with('sandi_baru', $sandi);
    }

    private function punyaProfil(User $user): bool
    {
        return DB::table('siswa')->where('id_user', $user->id_user)->exists()
            || DB::table('guru')->where('id_user', $user->id_user)->exists()
            || DB::table('orang_tua')->where('id_user', $user->id_user)->exists();
    }

    private function adminAktifLainnya(User $user): int
    {
        return User::where('role', 'admin')->where('is_active', true)
            ->where('id_user', '!=', $user->id_user)->count();
    }

    /** Password sementara yang mudah dibacakan: tanpa karakter mirip (0/O, 1/l/I). */
    private function sandiAcak(): string
    {
        $abjad = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';

        return collect(range(1, 10))->map(fn () => $abjad[random_int(0, strlen($abjad) - 1)])->implode('');
    }
}
