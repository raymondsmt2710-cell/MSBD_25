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


        DB::unprepared('DROP FUNCTION IF EXISTS fn_boleh_memilih');

        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_boleh_memilih(p_id_pemilihan INT, p_id_siswa INT)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT COUNT(*) INTO v_jumlah
    FROM pemilihan p
    JOIN daftar_pemilih dp
      ON dp.id_pemilihan = p.id_pemilihan
     AND dp.id_siswa = p_id_siswa
    WHERE p.id_pemilihan = p_id_pemilihan
      AND p.status = 'berlangsung'
      AND NOW() >= p.waktu_mulai
      AND NOW() < p.waktu_selesai
      AND dp.sudah_memilih = 0;

    RETURN IF(v_jumlah > 0, 1, 0);
END
SQL);


        DB::unprepared('DROP PROCEDURE IF EXISTS sp_rekam_suara');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_rekam_suara(
    IN p_id_pemilihan INT,
    IN p_id_siswa INT,
    IN p_id_kandidat INT,
    OUT p_status VARCHAR(40)
)
BEGIN
    DECLARE v_status VARCHAR(20) DEFAULT NULL;
    DECLARE v_mulai DATETIME DEFAULT NULL;
    DECLARE v_selesai DATETIME DEFAULT NULL;
    DECLARE v_pemilih INT DEFAULT 0;
    DECLARE v_kandidat INT DEFAULT NULL;
    DECLARE v_uuid CHAR(36) DEFAULT NULL;

    SELECT status, waktu_mulai, waktu_selesai
    INTO v_status, v_mulai, v_selesai
    FROM pemilihan
    WHERE id_pemilihan = p_id_pemilihan;

    IF v_status IS NULL THEN
        SET p_status = 'PEMILIHAN_TIDAK_VALID';
    ELSEIF v_status = 'draf' OR NOW() < v_mulai THEN
        SET p_status = 'BELUM_DIMULAI';
    ELSEIF v_status = 'selesai' OR NOW() >= v_selesai THEN
        SET p_status = 'SUDAH_DITUTUP';
    ELSE
        SELECT id_pemilihan INTO v_kandidat
        FROM kandidat
        WHERE id_kandidat = p_id_kandidat;

        IF v_kandidat IS NULL OR v_kandidat <> p_id_pemilihan THEN
            SET p_status = 'KANDIDAT_TIDAK_VALID';
        ELSE
            SELECT COUNT(*) INTO v_pemilih
            FROM daftar_pemilih
            WHERE id_pemilihan = p_id_pemilihan AND id_siswa = p_id_siswa;

            IF v_pemilih = 0 THEN
                SET p_status = 'BUKAN_PEMILIH';
            ELSE
                UPDATE daftar_pemilih
                SET sudah_memilih = 1, waktu_memilih = NOW()
                WHERE id_pemilihan = p_id_pemilihan
                  AND id_siswa = p_id_siswa
                  AND sudah_memilih = 0;

                IF ROW_COUNT() = 0 THEN
                    SET p_status = 'SUDAH_MEMILIH';
                ELSE
                    SET v_uuid = LOWER(CONCAT(
                        SUBSTR(HEX(RANDOM_BYTES(4)), 1, 8), '-',
                        SUBSTR(HEX(RANDOM_BYTES(2)), 1, 4), '-4',
                        SUBSTR(HEX(RANDOM_BYTES(2)), 1, 3), '-',
                        SUBSTR('89ab', FLOOR(1 + RAND() * 4), 1),
                        SUBSTR(HEX(RANDOM_BYTES(2)), 1, 3), '-',
                        SUBSTR(HEX(RANDOM_BYTES(6)), 1, 12)
                    ));

                    INSERT INTO suara (id_suara, id_pemilihan, id_kandidat)
                    VALUES (v_uuid, p_id_pemilihan, p_id_kandidat);

                    SET p_status = 'BERHASIL';
                END IF;
            END IF;
        END IF;
    END IF;
END
SQL);


        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');
        $akun = [
            env('DB_USERNAME_ADMIN', 'admin_app'),
            env('DB_USERNAME_GURU', 'guru_app'),
            env('DB_USERNAME_SISWA', 'siswa_app'),
        ];

        foreach ($akun as $user) {
            DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`fn_boleh_memilih` TO '{$user}'@'{$host}'");
            DB::unprepared("GRANT EXECUTE ON PROCEDURE `{$db}`.`sp_rekam_suara` TO '{$user}'@'{$host}'");
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }


        DB::unprepared('DROP FUNCTION IF EXISTS fn_boleh_memilih');

        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_boleh_memilih(p_id_pemilihan INT, p_id_siswa INT)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT COUNT(*) INTO v_jumlah
    FROM pemilihan p
    JOIN daftar_pemilih dp
      ON dp.id_pemilihan = p.id_pemilihan
     AND dp.id_siswa = p_id_siswa
    WHERE p.id_pemilihan = p_id_pemilihan
      AND p.status = 'berlangsung'
      AND NOW() BETWEEN p.waktu_mulai AND p.waktu_selesai
      AND dp.sudah_memilih = 0;

    RETURN IF(v_jumlah > 0, 1, 0);
END
SQL);

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');
        $akun = [
            env('DB_USERNAME_ADMIN', 'admin_app'),
            env('DB_USERNAME_GURU', 'guru_app'),
            env('DB_USERNAME_SISWA', 'siswa_app'),
        ];

        foreach ($akun as $user) {
            DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`fn_boleh_memilih` TO '{$user}'@'{$host}'");
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }
};
