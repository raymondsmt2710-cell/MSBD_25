<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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


                    $kode = (int) ($e->errorInfo[1] ?? 0);
                    if (in_array($kode, [1044, 1142, 1146, 1227], true)) {
                        Log::error("Migrasi 2026_11_18_000002: gagal memberi hak {$tabel} ke {$user}. Kode {$kode}: ".$e->getMessage());

                        continue;
                    }

                    throw $e;
                }
            }
        }

        // Orang tua tetap butuh hak tulis pada `sessions` bila SESSION_DRIVER=database:
        // tanpa itu login orang tua gagal dengan "INSERT command denied".
        // Yang dicabut hanya hak tulis cache, karena ortu tidak memakai cache.
        $ortu = env('DB_USERNAME_ORTU', 'ortu_app');
        foreach (['cache', 'cache_locks'] as $tabel) {
            try {
                DB::unprepared("REVOKE INSERT, UPDATE, DELETE ON `{$db}`.`{$tabel}` FROM '{$ortu}'@'{$host}'");
            } catch (QueryException $e) {

                if (! in_array((int) ($e->errorInfo[1] ?? 0), [1141, 1147], true)) {
                    throw $e;
                }
            }
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }


    public function down(): void
    {

    }
};
