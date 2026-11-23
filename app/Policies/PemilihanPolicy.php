<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Pemilihan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PemilihanPolicy
{
    public function kelola(User $user, ?Pemilihan $pemilihan = null): bool
    {
        if ($user->role === Role::Admin) {
            return true;
        }

        if ($user->role !== Role::Guru) {
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
        return $user->role === Role::Admin;
    }


    public function idYangBoleh(User $user): ?array
    {
        if ($user->role === Role::Admin) {
            return null;
        }

        return DB::table('panitia_pemilihan')
            ->where('id_user', $user->id_user)
            ->pluck('id_pemilihan')
            ->all();
    }
}
