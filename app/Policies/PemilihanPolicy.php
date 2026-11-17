<?php

namespace App\Policies;

use App\Models\Pemilihan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Lapis pengamanan aplikasi (lapis kedua setelah GRANT + trigger di database).
// Aturan: admin boleh semuanya; guru boleh bila tercatat sebagai panitia pemilihan itu.
class PemilihanPolicy
{
    public function kelola(User $user, ?Pemilihan $pemilihan = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role !== 'guru' || $pemilihan === null) {
            return false;
        }

        return DB::table('panitia_pemilihan')
            ->where('id_pemilihan', $pemilihan->id_pemilihan)
            ->where('id_user', $user->id_user)
            ->exists();
    }

    public function hanyaAdmin(User $user): bool
    {
        return $user->role === 'admin';
    }
}
