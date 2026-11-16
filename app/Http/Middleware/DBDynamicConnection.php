<?php

namespace App\Http\Middleware;

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
            $target = self::KONEKSI_ROLE[Auth::user()->role] ?? null;

            if ($target && Config::has("database.connections.{$target}")) {
                try {
                    DB::connection($target)->getPdo();
                    Config::set('database.default', $target);
                    DB::setDefaultConnection($target);
                } catch (\Throwable $e) {
                    // Sama seperti materi: jika user MySQL belum dibuat, kembali ke koneksi bawaan
                    Log::warning("Gagal memakai koneksi {$target}: ".$e->getMessage());
                    Config::set('database.default', 'mysql');
                    DB::setDefaultConnection('mysql');
                }
            }
        }

        return $next($request);
    }
}
