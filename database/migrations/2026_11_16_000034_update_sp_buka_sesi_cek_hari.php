<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function beriHakGuru(): void
    {
        $db = config('database.connections.mysql.database');
        $guru = env('DB_USERNAME_GURU', 'guru_app');
        $host = env('DB_USER_HOST', 'localhost');

        DB::unprepared("GRANT EXECUTE ON PROCEDURE `{$db}`.`sp_buka_sesi` TO '{$guru}'@'{$host}'");
        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_buka_sesi');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_buka_sesi(
    IN p_id_jadwal INT,
    IN p_id_guru INT,
    OUT p_status VARCHAR(30),
    OUT p_id_sesi INT
)
BEGIN
    DECLARE v_pemilik INT DEFAULT NULL;
    DECLARE v_hari VARCHAR(10) DEFAULT NULL;
    DECLARE v_sesi INT DEFAULT NULL;

    SELECT id_guru, hari INTO v_pemilik, v_hari
    FROM jadwal_pelajaran
    WHERE id_jadwal = p_id_jadwal;

    IF v_pemilik IS NULL OR v_pemilik <> p_id_guru THEN
        SET p_status = 'JADWAL_TIDAK_VALID';
        SET p_id_sesi = NULL;
    ELSEIF v_hari <> ELT(WEEKDAY(CURDATE()) + 1, 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu') THEN
        SET p_status = 'BUKAN_HARI_JADWAL';
        SET p_id_sesi = NULL;
    ELSE
        SELECT id_sesi INTO v_sesi
        FROM sesi_presensi
        WHERE id_jadwal = p_id_jadwal AND tanggal = CURDATE()
        LIMIT 1;

        IF v_sesi IS NOT NULL THEN
            SET p_status = 'SESI_SUDAH_ADA';
            SET p_id_sesi = v_sesi;
        ELSE
            INSERT INTO sesi_presensi (id_jadwal, tanggal, dibuka_oleh, dibuka_pada, status)
            VALUES (p_id_jadwal, CURDATE(), p_id_guru, NOW(), 'dibuka');

            SET p_status = 'BERHASIL';
            SET p_id_sesi = LAST_INSERT_ID();
        END IF;
    END IF;
END
SQL);

        $this->beriHakGuru();
    }


    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_buka_sesi');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_buka_sesi(
    IN p_id_jadwal INT,
    IN p_id_guru INT,
    OUT p_status VARCHAR(30),
    OUT p_id_sesi INT
)
BEGIN
    DECLARE v_pemilik INT DEFAULT NULL;
    DECLARE v_sesi INT DEFAULT NULL;

    SELECT id_guru INTO v_pemilik
    FROM jadwal_pelajaran
    WHERE id_jadwal = p_id_jadwal;

    IF v_pemilik IS NULL OR v_pemilik <> p_id_guru THEN
        SET p_status = 'JADWAL_TIDAK_VALID';
        SET p_id_sesi = NULL;
    ELSE
        SELECT id_sesi INTO v_sesi
        FROM sesi_presensi
        WHERE id_jadwal = p_id_jadwal AND tanggal = CURDATE()
        LIMIT 1;

        IF v_sesi IS NOT NULL THEN
            SET p_status = 'SESI_SUDAH_ADA';
            SET p_id_sesi = v_sesi;
        ELSE
            INSERT INTO sesi_presensi (id_jadwal, tanggal, dibuka_oleh, dibuka_pada, status)
            VALUES (p_id_jadwal, CURDATE(), p_id_guru, NOW(), 'dibuka');

            SET p_status = 'BERHASIL';
            SET p_id_sesi = LAST_INSERT_ID();
        END IF;
    END IF;
END
SQL);

        $this->beriHakGuru();
    }
};
