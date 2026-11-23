<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DBDynamicConnection
{
    private const KONEKSI_ROLE = [
        'admin'     => 'mysql_admin',
        'guru'      => 'mysql_guru',
        'siswa'     => 'mysql_siswa',



        'bendahara_kas' => 'mysql_siswa',
        'orang_tua' => 'mysql_orangtua',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $asal = DB::getDefaultConnection();




        if (app()->runningUnitTests()) {
            return $next($request);
        }


        if (Config::get('database.default') !== $asal) {
            Config::set('database.default', $asal);
            DB::setDefaultConnection($asal);
        }

        $this->pakaiKoneksiRole($asal);

        try {
            return $next($request);
        } finally {

            Config::set('database.default', $asal);
            DB::setDefaultConnection($asal);
        }
    }

    private function pakaiKoneksiRole(string $asal): void
    {
        if (! Auth::check()) {
            return;
        }

        $role = Auth::user()->role;
        $role = $role instanceof Role ? $role->value : (string) $role;
        $target = self::KONEKSI_ROLE[$role] ?? null;

        if (! $target || ! Config::has("database.connections.{$target}")) {
            return;
        }

        try {
            DB::connection($target)->getPdo();
            Config::set('database.default', $target);
            DB::setDefaultConnection($target);
        } catch (\Throwable $e) {



            if (filter_var(env('DB_ROLE_CONNECTION_STRICT', false), FILTER_VALIDATE_BOOLEAN)) {
                throw new \RuntimeException(
                    "Koneksi {$target} gagal dan DB_ROLE_CONNECTION_STRICT aktif: ".$e->getMessage(),
                    0,
                    $e,
                );
            }



            Log::warning("Gagal memakai koneksi {$target}: ".$e->getMessage());
            Config::set('database.default', $asal);
            DB::setDefaultConnection($asal);
        }
    }
}
