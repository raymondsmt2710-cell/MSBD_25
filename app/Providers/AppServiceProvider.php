<?php

namespace App\Providers;

use App\Models\Pemilihan;
use App\Policies\PemilihanPolicy;
use App\Policies\PerangkatPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Pemilihan::class, PemilihanPolicy::class);

        // Fitur perangkat organisasi & kelas tidak punya model tunggal,
        // jadi didaftarkan sebagai ability berbasis policy.
        Gate::define('perangkat.lihat', [PerangkatPolicy::class, 'lihat']);
        Gate::define('perangkat.kelola', [PerangkatPolicy::class, 'kelola']);
        Gate::define('perangkat.kelolaKelas', [PerangkatPolicy::class, 'kelolaKelas']);
        Gate::define('perangkat.kelolaOrganisasi', [PerangkatPolicy::class, 'kelolaOrganisasi']);
    }
}
