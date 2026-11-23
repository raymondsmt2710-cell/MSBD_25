<?php

namespace App\Policies;

use App\Enums\Role;
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
 *
 * Catatan penting: User::$role di-cast menjadi App\Enums\Role, sehingga
 * perbandingan dengan string biasa ("admin") selalu bernilai false.
 * Semua pemeriksaan di bawah memakai peran() yang menormalkan enum ke string.
 */
class PerangkatPolicy
{
    /** Peran pengguna sebagai string ('admin', 'guru', 'siswa', 'orang_tua'). */
    private function peran(User $user): string
    {
        return $user->role instanceof Role ? $user->role->value : (string) $user->role;
    }

    /** Melihat daftar kelas & organisasi beserta pengurusnya. */
    public function lihat(User $user): bool
    {
        // 'bendahara_kas' adalah siswa dengan hak tambahan di kas, jadi tetap
        // boleh melihat perangkat seperti siswa biasa.
        return in_array($this->peran($user), ['admin', 'guru', 'siswa', 'bendahara_kas'], true);
    }

    /** Boleh membuka halaman kelola (daftar yang bisa dikelola). */
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

    /** Boleh mengubah susunan pengurus kelas ini? */
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

    /** Boleh mengubah susunan pengurus organisasi ini? */
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

    /** ID kelas yang menjadi tanggung jawab guru ini (wali kelas). */
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

    /** ID organisasi yang menjadi tanggung jawab guru ini (pembina). */
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
