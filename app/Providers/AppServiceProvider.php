<?php

namespace App\Providers;

use App\Models\Kelas;
use App\Models\Pemilihan;
use App\Models\User;
use App\Policies\PemilihanPolicy;
use App\Policies\PerangkatPolicy;
use App\Support\Akses;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Akses::class);
    }

    public function boot(): void
    {
        // Otorisasi terpusat (lihat docs/superpowers/specs/2026-11-16-kas-dan-denah-design.md).
        Gate::define('kas.lihat', fn (User $user, Kelas $kelas) => app(Akses::class)->bisaLihatKelas($user, $kelas));
        Gate::define('kas.kelola', fn (User $user, Kelas $kelas) => app(Akses::class)->bisaKelolaKas($user, $kelas));
        Gate::define('denah.kelola-kursi', fn (User $user, Kelas $kelas) => app(Akses::class)->bisaKelolaKursi($user, $kelas));
        Gate::define('denah.kelola-ruangan', fn (User $user) => app(Akses::class)->bisaKelolaRuangan($user));

        // Fitur e-voting (dari main).
        Gate::policy(Pemilihan::class, PemilihanPolicy::class);

        // Fitur perangkat organisasi & kelas tidak punya model tunggal,
        // jadi didaftarkan sebagai ability berbasis policy.
        Gate::define('perangkat.lihat', [PerangkatPolicy::class, 'lihat']);
        Gate::define('perangkat.kelola', [PerangkatPolicy::class, 'kelola']);
        Gate::define('perangkat.kelolaKelas', [PerangkatPolicy::class, 'kelolaKelas']);
        Gate::define('perangkat.kelolaOrganisasi', [PerangkatPolicy::class, 'kelolaOrganisasi']);
    }
}
