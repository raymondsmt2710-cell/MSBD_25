<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

        DB::unprepared('DROP FUNCTION IF EXISTS fn_jumlah_presensi');

        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_jumlah_presensi(p_id_siswa INT, p_status VARCHAR(10))
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT COUNT(*) INTO v_jumlah
    FROM presensi
    WHERE id_siswa = p_id_siswa
      AND status = p_status COLLATE utf8mb4_unicode_ci;

    RETURN v_jumlah;
END
SQL);




        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');

        foreach ([
            env('DB_USERNAME_ADMIN', 'admin_app'),
            env('DB_USERNAME_GURU', 'guru_app'),
            env('DB_USERNAME_SISWA', 'siswa_app'),
            env('DB_USERNAME_ORTU', 'ortu_app'),
        ] as $user) {
            DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`fn_jumlah_presensi` TO '{$user}'@'{$host}'");
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared('DROP FUNCTION IF EXISTS fn_jumlah_presensi');

        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_jumlah_presensi(p_id_siswa INT, p_status VARCHAR(10))
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT COUNT(*) INTO v_jumlah
    FROM presensi
    WHERE id_siswa = p_id_siswa AND status = p_status;

    RETURN v_jumlah;
END
SQL);
    }
};
