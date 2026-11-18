<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Hak akses fitur perangkat organisasi & kelas.
 *
 * Aturan:
 *  - admin       : boleh semuanya;
 *  - guru        : hanya kelas yang menjadi wali kelasnya, dan organisasi yang
 *                  menjadi pembinanya;
 *  - siswa       : hanya melihat;
 *  - orang_tua   : tidak punya akses sama sekali ke fitur ini.
 */
class PerangkatPolicy
{
    /** Melihat daftar kelas & organisasi beserta pengurusnya. */
    public function lihat(User $user): bool
    {
        return in_array($user->role, ['admin', 'guru', 'siswa'], true);
    }

    /** Boleh membuka halaman kelola (daftar yang bisa dikelola). */
    public function kelola(User $user): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role !== 'guru') {
            return false;
        }

        return $this->kelasYangBoleh($user) !== [] || $this->organisasiYangBoleh($user) !== [];
    }

    /** Boleh mengubah susunan pengurus kelas ini? */
    public function kelolaKelas(User $user, int $idKelas): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role !== 'guru') {
            return false;
        }

        return in_array($idKelas, $this->kelasYangBoleh($user), true);
    }

    /** Boleh mengubah susunan pengurus organisasi ini? */
    public function kelolaOrganisasi(User $user, int $idOrganisasi): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role !== 'guru') {
            return false;
        }

        return in_array($idOrganisasi, $this->organisasiYangBoleh($user), true);
    }

    /** ID kelas yang menjadi tanggung jawab guru ini (wali kelas). */
    public function kelasYangBoleh(User $user): array
    {
        if ($user->role !== 'guru') {
            return [];
        }

        $idGuru = DB::table('guru')->where('id_user', $user->id_user)->value('id_guru');

        if (! $idGuru) {
            return [];
        }

        return DB::table('kelas')->where('id_wali_kelas', $idGuru)
            ->pluck('id_kelas')->map(fn ($v) => (int) $v)->all();
    }

    /** ID organisasi yang menjadi tanggung jawab guru ini (pembina). */
    public function organisasiYangBoleh(User $user): array
    {
        if ($user->role !== 'guru') {
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
