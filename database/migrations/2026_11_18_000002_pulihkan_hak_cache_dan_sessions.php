<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// DCL: memulihkan hak akses tabel cache, cache_locks, dan sessions untuk user MySQL per role.
//
// Latar belakang: 2026_11_16_000038 berjalan SEBELUM 2026_11_16_160541_create_sessions_table.
// GRANT pada `sessions` gagal (1146, tabel belum ada), dan karena seluruh loop dibungkus satu
// try/catch yang hanya mencatat warning, guru_app/siswa_app/ortu_app tidak pernah menerima hak
// atas cache maupun sessions pada instalasi baru.
//
// Saat ini belum berdampak karena middleware throttle dijalankan sebelum DBDynamicConnection
// (prioritas middleware Laravel), tetapi pemakaian Cache:: di controller akan langsung error 1142.
//
// Migrasi ini berjalan setelah semua tabel itu ada, dan memberi hak per tabel per user sehingga
// satu kegagalan tidak menghentikan pemberian hak yang lain. Idempoten.
//
// ortu_app SENGAJA tidak diberi, malah dicabut: portal orang tua hanya-baca dan tidak memakai
// throttle, sedangkan HakEksekusiRoutineTest mewajibkan ortu_app tanpa hak INSERT/UPDATE/DELETE.
// Pencabutan diperlukan karena database yang dulu sempat menjalankan 000038 dengan sukses
// sudah terlanjur memberi ortu_app hak tulis pada tabel-tabel ini.
return new class extends Migration
{
    private const TABEL = ['cache', 'cache_locks', 'sessions'];

    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function akun(): array
    {
        return [
            env('DB_USERNAME_ADMIN', 'admin_app'),
            env('DB_USERNAME_GURU', 'guru_app'),
            env('DB_USERNAME_SISWA', 'siswa_app'),
        ];
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        if (filter_var(env('DB_SKIP_ROLE_USERS', false), FILTER_VALIDATE_BOOLEAN)) {
            Log::warning('Migrasi 2026_11_18_000002 dilewati karena DB_SKIP_ROLE_USERS=true.');

            return;
        }

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');

        foreach ($this->akun() as $user) {
            foreach (self::TABEL as $tabel) {
                try {
                    DB::unprepared("GRANT SELECT, INSERT, UPDATE, DELETE ON `{$db}`.`{$tabel}` TO '{$user}'@'{$host}'");
                } catch (QueryException $e) {
                    // 1044/1142/1227 = tidak punya hak GRANT; 1146 = tabel tidak ada.
                    // Dicatat sebagai error (bukan warning) agar tidak terlewat seperti pada 000038.
                    $kode = (int) ($e->errorInfo[1] ?? 0);
                    if (in_array($kode, [1044, 1142, 1146, 1227], true)) {
                        Log::error("Migrasi 2026_11_18_000002: gagal memberi hak {$tabel} ke {$user}. Kode {$kode}: ".$e->getMessage());

                        continue;
                    }

                    throw $e;
                }
            }
        }

        $ortu = env('DB_USERNAME_ORTU', 'ortu_app');
        foreach (self::TABEL as $tabel) {
            try {
                DB::unprepared("REVOKE INSERT, UPDATE, DELETE ON `{$db}`.`{$tabel}` FROM '{$ortu}'@'{$host}'");
            } catch (QueryException $e) {
                // 1141/1147 = hak memang tidak pernah diberikan; itu keadaan yang diinginkan.
                if (! in_array((int) ($e->errorInfo[1] ?? 0), [1141, 1147], true)) {
                    throw $e;
                }
            }
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }

    /** Tidak mencabut hak: mencabutnya akan mematikan session/cache aplikasi yang sedang berjalan. */
    public function down(): void
    {
        //
    }
};
