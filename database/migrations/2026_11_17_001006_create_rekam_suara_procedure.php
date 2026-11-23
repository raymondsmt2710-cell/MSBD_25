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
    ELSEIF v_status = 'selesai' OR NOW() > v_selesai THEN
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
                -- Kunci anti suara ganda: hanya berhasil bila belum memilih.
                UPDATE daftar_pemilih
                SET sudah_memilih = 1, waktu_memilih = NOW()
                WHERE id_pemilihan = p_id_pemilihan
                  AND id_siswa = p_id_siswa
                  AND sudah_memilih = 0;

                IF ROW_COUNT() = 0 THEN
                    SET p_status = 'SUDAH_MEMILIH';
                ELSE
                    -- UUID v4 acak murni. BUKAN UUID() milik MySQL: itu UUID v1 yang
                    -- menyimpan stempel waktu, sehingga urutan baris suara bisa
                    -- dicocokkan dengan waktu_memilih dan pilihan tiap siswa bisa
                    -- direkonstruksi.
                    SET v_uuid = LOWER(CONCAT(
                        SUBSTR(HEX(RANDOM_BYTES(4)), 1, 8), '-',
                        SUBSTR(HEX(RANDOM_BYTES(2)), 1, 4), '-4',
                        SUBSTR(HEX(RANDOM_BYTES(2)), 1, 3), '-',
                        SUBSTR('89ab', FLOOR(1 + RAND() * 4), 1),
                        SUBSTR(HEX(RANDOM_BYTES(2)), 1, 3), '-',
                        SUBSTR(HEX(RANDOM_BYTES(6)), 1, 12)
                    ));

                    -- Tanpa id_siswa: tidak ada kolom yang menghubungkan pemilih
                    -- dengan kandidat pilihannya.
                    INSERT INTO suara (id_suara, id_pemilihan, id_kandidat)
                    VALUES (v_uuid, p_id_pemilihan, p_id_kandidat);

                    SET p_status = 'BERHASIL';
                END IF;
            END IF;
        END IF;
    END IF;
END
SQL);
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_rekam_suara');
    }
};
