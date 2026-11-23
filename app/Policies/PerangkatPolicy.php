<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PerangkatPolicy
{

    private function peran(User $user): string
    {
        return $user->role instanceof Role ? $user->role->value : (string) $user->role;
    }


    public function lihat(User $user): bool
    {


        return in_array($this->peran($user), ['admin', 'guru', 'siswa', 'bendahara_kas'], true);
    }


    public function kelola(User $user): bool
    {
        if ($this->peran($user) === Role::Admin->value) {
            return true;
        }

        if ($this->peran($user) !== Role::Guru->value) {
            return false;
        }

        return $this->kelasYangBoleh($user) !== [] || $this->organisasiYangBoleh($user) !== [];
    }


    public function kelolaKelas(User $user, int $idKelas): bool
    {
        if ($this->peran($user) === Role::Admin->value) {
            return true;
        }

        if ($this->peran($user) !== Role::Guru->value) {
            return false;
        }

        return in_array($idKelas, $this->kelasYangBoleh($user), true);
    }


    public function kelolaOrganisasi(User $user, int $idOrganisasi): bool
    {
        if ($this->peran($user) === Role::Admin->value) {
            return true;
        }

        if ($this->peran($user) !== Role::Guru->value) {
            return false;
        }

        return in_array($idOrganisasi, $this->organisasiYangBoleh($user), true);
    }


    public function kelasYangBoleh(User $user): array
    {
        if ($this->peran($user) !== Role::Guru->value) {
            return [];
        }

        $idGuru = DB::table('guru')->where('id_user', $user->id_user)->value('id_guru');

        if (! $idGuru) {
            return [];
        }

        return DB::table('kelas')->where('id_wali_kelas', $idGuru)
            ->pluck('id_kelas')->map(fn ($v) => (int) $v)->all();
    }


    public function organisasiYangBoleh(User $user): array
    {
        if ($this->peran($user) !== Role::Guru->value) {
            return [];
        }

        $idGuru = DB::table('guru')->where('id_user', $user->id_user)->value('id_guru');

        if (! $idGuru) {
            return [];
        }

        return DB::table('organisasi')->where('id_pembina', $idGuru)
            ->pluck('id_organisasi')->map(fn ($v) => (int) $v)->all();
    }
}
