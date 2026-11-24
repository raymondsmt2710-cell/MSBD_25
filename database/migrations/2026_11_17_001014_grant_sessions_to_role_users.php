<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }


    private function accounts(): array
    {
        return [
            env('DB_USERNAME_ADMIN', 'admin_app'),
            env('DB_USERNAME_GURU', 'guru_app'),
            env('DB_USERNAME_SISWA', 'siswa_app'),
            env('DB_USERNAME_ORTU', 'ortu_app'),
        ];
    }

    /**
     * Host akun MySQL per peran. Harus mengikuti DB_USER_HOST supaya GRANT
     * berlaku saat database berada di container/host terpisah (mis. Railway).
     */
    private function userHost(): string
    {
        return env('DB_USER_HOST', 'localhost');
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = $this->userHost();


        if (! Schema::hasTable('sessions')) {
            return;
        }

        foreach ($this->accounts() as $user) {
            DB::unprepared(
                "GRANT SELECT, INSERT, UPDATE, DELETE ON `{$db}`.`sessions` TO '{$user}'@'{$host}'"
            );
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = $this->userHost();

        if (! Schema::hasTable('sessions')) {
            return;
        }

        foreach ($this->accounts() as $user) {
            DB::unprepared("REVOKE ALL PRIVILEGES ON `{$db}`.`sessions` FROM '{$user}'@'{$host}'");
        }
    }
};
