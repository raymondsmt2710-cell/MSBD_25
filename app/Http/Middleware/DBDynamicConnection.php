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

// Mengganti user MySQL sesuai role yang login (materi bagian 6). Didaftarkan dengan append() di grup web,
// sehingga berjalan SETELAH session dimuat.
class DBDynamicConnection
{
    private const KONEKSI_ROLE = [
        'admin'     => 'mysql_admin',
        'guru'      => 'mysql_guru',
        'siswa'     => 'mysql_siswa',
        'orang_tua' => 'mysql_orangtua',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $role = Auth::user()->role;
            $role = $role instanceof Role ? $role->value : (string) $role;
            $target = self::KONEKSI_ROLE[$role] ?? null;
            $asal = DB::getDefaultConnection();

            if ($target && Config::has("database.connections.{$target}")) {
                try {
                    DB::connection($target)->getPdo();
                    Config::set('database.default', $target);
                    DB::setDefaultConnection($target);
                } catch (\Throwable $e) {
                    // Jika user MySQL belum dibuat, kembali ke koneksi bawaan aplikasi (mis. sqlite),
                    // bukan dipaksa ke mysql yang belum tentu tersedia.
                    Log::warning("Gagal memakai koneksi {$target}: ".$e->getMessage());
                    Config::set('database.default', $asal);
                    DB::setDefaultConnection($asal);
                }
            }
        }

        return $next($request);
    }
}
