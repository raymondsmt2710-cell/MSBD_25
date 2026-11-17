<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Token QR sekarang dibuat langsung oleh controller guru (bukan lewat sp_buat_token_presensi)
// supaya masa berlakunya mengikuti jam selesai jadwal, bukan durasi detik.
// Karena itu guru_app memerlukan hak INSERT pada token_presensi.
return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');
        $guru = env('DB_USERNAME_GURU', 'guru_app');

        try {
            DB::unprepared("GRANT SELECT, INSERT ON `{$db}`.`token_presensi` TO '{$guru}'@'{$host}'");
            DB::unprepared('FLUSH PRIVILEGES');
        } catch (\Throwable $e) {
            Log::warning('Migration 000037: gagal memberi hak INSERT token_presensi. Pesan: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');
        $guru = env('DB_USERNAME_GURU', 'guru_app');

        DB::unprepared("REVOKE INSERT ON `{$db}`.`token_presensi` FROM '{$guru}'@'{$host}'");
        DB::unprepared('FLUSH PRIVILEGES');
    }
};
