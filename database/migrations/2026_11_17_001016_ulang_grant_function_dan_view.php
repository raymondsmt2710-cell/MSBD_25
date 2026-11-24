<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }


    private function accounts(): array
    {
        return [
            env('DB_USERNAME_GURU', 'guru_app'),
            env('DB_USERNAME_SISWA', 'siswa_app'),
            env('DB_USERNAME_ORTU', 'ortu_app'),
        ];
    }


    private function functions(): array
    {
        return [
            'fn_jumlah_presensi',
            'fn_persentase_kehadiran',
            'fn_token_valid',
            'fn_boleh_memilih',
            'fn_total_suara',
        ];
    }


    private function views(): array
    {
        return [
            'v_rekap_presensi_siswa',
            'v_rekap_sesi_presensi',
            'v_pemilihan_pengelola',
        ];
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');

        foreach ($this->accounts() as $user) {
            foreach ($this->functions() as $fn) {
                $ada = DB::selectOne(
                    'SELECT COUNT(*) AS n FROM information_schema.routines
                     WHERE routine_schema = ? AND routine_name = ?',
                    [$db, $fn],
                );

                if ((int) ($ada->n ?? 0) === 0) {
                    continue;
                }

                try {
                    DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`{$fn}` TO '{$user}'@'{$host}'");
                } catch (\Throwable $e) {
                    Log::warning("Lewati GRANT EXECUTE {$fn} untuk {$user}: ".$e->getMessage());
                }
            }

            foreach ($this->views() as $view) {
                $ada = DB::selectOne(
                    'SELECT COUNT(*) AS n FROM information_schema.views
                     WHERE table_schema = ? AND table_name = ?',
                    [$db, $view],
                );

                if ((int) ($ada->n ?? 0) === 0) {
                    continue;
                }

                try {
                    DB::unprepared("GRANT SELECT ON `{$db}`.`{$view}` TO '{$user}'@'{$host}'");
                } catch (\Throwable $e) {
                    Log::warning("Lewati GRANT SELECT {$view} untuk {$user}: ".$e->getMessage());
                }
            }
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {

    }
};
