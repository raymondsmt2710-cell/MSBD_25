<?php

namespace App\Policies;

use App\Models\Pemilihan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Lapis pengamanan aplikasi (lapis kedua setelah GRANT + trigger di database).
//
// Aturan:
//  - admin: boleh semuanya;
//  - guru yang tercatat sebagai panitia: boleh, tetapi hanya untuk pemilihan yang
//    ditugaskan kepadanya;
//  - selain itu: tidak boleh.
//
// Pemilihan tanpa primary key (mis. `new Pemilihan` untuk halaman daftar/form baru)
// berarti "apakah user ini panitia pada pemilihan mana pun". Pemeriksaan per-pemilihan
// tetap dilakukan pada setiap aksi yang menyentuh satu pemilihan tertentu.
class PemilihanPolicy
{
    public function kelola(User $user, ?Pemilihan $pemilihan = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role !== 'guru') {
            return false;
        }

        $query = DB::table('panitia_pemilihan')->where('id_user', $user->id_user);

        if ($pemilihan !== null && $pemilihan->id_pemilihan !== null) {
            $query->where('id_pemilihan', $pemilihan->id_pemilihan);
        }

        return $query->exists();
    }

    public function hanyaAdmin(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Membuat pemilihan BARU hanya boleh admin.
     *
     * Panitia (guru) boleh mengelola pemilihan yang sudah ditugaskan kepadanya —
     * kandidat, pemilih, monitor, buka/tutup — tetapi tidak boleh menggelar
     * pemilihan baru. Dipisahkan dari kelola() supaya kedua hak itu tidak
     * tercampur dalam satu nama.
     */
    public function buat(User $user): bool
    {
        return $user->role === 'admin';
    }

    /** ID pemilihan yang boleh dikelola user ini; null berarti semua (admin). */
    public function idYangBoleh(User $user): ?array
    {
        if ($user->role === 'admin') {
            return null;
        }

        return DB::table('panitia_pemilihan')
            ->where('id_user', $user->id_user)
            ->pluck('id_pemilihan')
            ->all();
    }
}
