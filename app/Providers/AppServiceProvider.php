<?php

namespace App\Providers;

use App\Models\IuranKas;
use App\Models\Kelas;
use App\Models\Pemilihan;
use App\Models\User;
use App\Policies\PemilihanPolicy;
use App\Policies\PerangkatPolicy;
use App\Support\Akses;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Akses::class);
    }

    public function boot(): void
    {
        
        
        
        //
        
        
        
        
        
        
        $host = request()->getHost();

        if (str_ends_with($host, '.trycloudflare.com')) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        } elseif ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);
        }

        
        Gate::define('kas.lihat', fn (User $user, Kelas $kelas) => app(Akses::class)->bisaLihatKelas($user, $kelas));
        Gate::define('kas.kelola', fn (User $user, Kelas $kelas) => app(Akses::class)->bisaKelolaKas($user, $kelas));
        
        
        Gate::define('iuran.lapor', fn (User $user, IuranKas $iuran) => app(Akses::class)->bisaLaporIuran($user, $iuran));
        Gate::define('denah.kelola-kursi', fn (User $user, Kelas $kelas) => app(Akses::class)->bisaKelolaKursi($user, $kelas));
        Gate::define('denah.kelola-ruangan', fn (User $user) => app(Akses::class)->bisaKelolaRuangan($user));

        
        Gate::policy(Pemilihan::class, PemilihanPolicy::class);

        
        
        Gate::define('perangkat.lihat', [PerangkatPolicy::class, 'lihat']);
        Gate::define('perangkat.kelola', [PerangkatPolicy::class, 'kelola']);
        Gate::define('perangkat.kelolaKelas', [PerangkatPolicy::class, 'kelolaKelas']);
        Gate::define('perangkat.kelolaOrganisasi', [PerangkatPolicy::class, 'kelolaOrganisasi']);
    }
}
