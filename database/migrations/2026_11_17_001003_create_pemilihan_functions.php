<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private array $functions = [
        'fn_sudah_memilih',
        'fn_boleh_memilih',
        'fn_total_suara',
        'fn_partisipasi',
    ];

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        foreach ($this->functions as $f) {
            DB::unprepared("DROP FUNCTION IF EXISTS {$f}");
        }


        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_sudah_memilih(p_id_pemilihan INT, p_id_siswa INT)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT COUNT(*) INTO v_jumlah
    FROM daftar_pemilih
    WHERE id_pemilihan = p_id_pemilihan
      AND id_siswa = p_id_siswa
      AND sudah_memilih = 1;

    RETURN IF(v_jumlah > 0, 1, 0);
END
SQL);



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


        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_total_suara(p_id_pemilihan INT)
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT COUNT(*) INTO v_jumlah
    FROM suara
    WHERE id_pemilihan = p_id_pemilihan;

    RETURN v_jumlah;
END
SQL);



        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_partisipasi(p_id_pemilihan INT)
RETURNS DECIMAL(5,2)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_sudah INT DEFAULT 0;

    SELECT COUNT(*), COALESCE(SUM(sudah_memilih = 1), 0)
    INTO v_total, v_sudah
    FROM daftar_pemilih
    WHERE id_pemilihan = p_id_pemilihan;

    IF v_total = 0 THEN
        RETURN 0.00;
    END IF;

    RETURN ROUND(v_sudah * 100 / v_total, 2);
END
SQL);
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        foreach ($this->functions as $f) {
            DB::unprepared("DROP FUNCTION IF EXISTS {$f}");
        }
    }
};
