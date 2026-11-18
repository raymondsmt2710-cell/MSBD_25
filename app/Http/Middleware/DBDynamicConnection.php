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
//
// Penting: koneksi default WAJIB dikembalikan ke koneksi aplikasi setelah response selesai.
// Tanpa ini, pada proses yang menangani banyak request berurutan (queue worker, octane,
// atau test) koneksi role milik request sebelumnya terbawa ke request berikutnya sehingga
// user lain memakai hak akses role yang salah ("SELECT command denied ... for table").
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
        $asal = DB::getDefaultConnection();

        // Saat testing, pergantian koneksi per-role tidak dipakai: setiap test
        // memakai satu koneksi yang sama, dan perpindahan koneksi akan membuat
        // data test tidak terlihat (query mengembalikan 0 baris).
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        // Selalu mulai dari koneksi aplikasi, bukan sisa request sebelumnya.
        if (Config::get('database.default') !== $asal) {
            Config::set('database.default', $asal);
            DB::setDefaultConnection($asal);
        }

        $this->pakaiKoneksiRole($asal);

        try {
            return $next($request);
        } finally {
            // Kembalikan ke koneksi aplikasi agar tidak bocor ke request berikutnya.
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
            // Set DB_ROLE_CONNECTION_STRICT=true agar kegagalan ini justru menghentikan
            // request (fail-closed) dan tidak diam-diam memakai koneksi root — penting saat
            // production agar GRANT benar-benar diuji.
            if (filter_var(env('DB_ROLE_CONNECTION_STRICT', false), FILTER_VALIDATE_BOOLEAN)) {
                throw new \RuntimeException(
                    "Koneksi {$target} gagal dan DB_ROLE_CONNECTION_STRICT aktif: ".$e->getMessage(),
                    0,
                    $e,
                );
            }

            // Jika user MySQL belum dibuat, tetap pakai koneksi bawaan aplikasi (mis. sqlite),
            // bukan dipaksa ke mysql yang belum tentu tersedia.
            Log::warning("Gagal memakai koneksi {$target}: ".$e->getMessage());
            Config::set('database.default', $asal);
            DB::setDefaultConnection($asal);
        }
    }
}
