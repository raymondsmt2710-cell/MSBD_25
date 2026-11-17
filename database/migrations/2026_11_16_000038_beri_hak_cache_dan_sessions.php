<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Laravel memakai cache store untuk throttle / rate-limit (mis. middleware `throttle`
// pada route scan). CACHE_STORE=database berarti setiap request bisa menulis ke tabel
// `cache` dan `cache_locks`. Tanpa hak ini, siswa_app/guru_app gagal dengan
// "1142 SELECT command denied ... for table 'cache'" tepat setelah presensi tercatat.
//
// Karena itu keempat user role diberi hak pada tabel cache dan sessions.
return new class extends Migration
{
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
            env('DB_USERNAME_ORTU', 'ortu_app'),
        ];
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');

        $tabel = [
            'cache'       => 'SELECT, INSERT, UPDATE, DELETE',
            'cache_locks' => 'SELECT, INSERT, UPDATE, DELETE',
            'sessions'    => 'SELECT, INSERT, UPDATE, DELETE',
        ];

        try {
            foreach ($this->akun() as $user) {
                foreach ($tabel as $nama => $hak) {
                    DB::unprepared("GRANT {$hak} ON `{$db}`.`{$nama}` TO '{$user}'@'{$host}'");
                }
            }

            DB::unprepared('FLUSH PRIVILEGES');
        } catch (\Throwable $e) {
            Log::warning('Migration 000038: gagal memberi hak cache/sessions. Pesan: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');

        foreach ($this->akun() as $user) {
            foreach (['cache', 'cache_locks', 'sessions'] as $nama) {
                try {
                    DB::unprepared("REVOKE ALL PRIVILEGES ON `{$db}`.`{$nama}` FROM '{$user}'@'{$host}'");
                } catch (\Throwable $e) {
                    // hak mungkin belum pernah diberikan
                }
            }
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }
};
