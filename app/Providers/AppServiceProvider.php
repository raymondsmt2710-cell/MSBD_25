<?php

namespace App\Providers;

use App\Models\Kelas;
use App\Models\User;
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
    }
}
