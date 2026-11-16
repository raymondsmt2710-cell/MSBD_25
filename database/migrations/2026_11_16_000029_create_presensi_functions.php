<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Stored Function presensi (materi Stored Function): hanya MEMBACA data, dipanggil lewat SELECT dari Model.
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

        foreach (['fn_persentase_kehadiran', 'fn_jumlah_presensi', 'fn_token_valid'] as $f) {
            DB::unprepared("DROP FUNCTION IF EXISTS {$f}");
        }

        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_persentase_kehadiran(p_id_siswa INT)
RETURNS DECIMAL(5,2)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_hadir INT DEFAULT 0;

    SELECT COUNT(*), COALESCE(SUM(status = 'hadir'), 0)
    INTO v_total, v_hadir
    FROM presensi
    WHERE id_siswa = p_id_siswa;

    IF v_total = 0 THEN
        RETURN 0.00;
    END IF;

    RETURN ROUND(v_hadir * 100 / v_total, 2);
END
SQL);

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

        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_token_valid(p_kode VARCHAR(64))
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT COUNT(*) INTO v_jumlah
    FROM token_presensi t
    JOIN sesi_presensi s ON s.id_sesi = t.id_sesi
    WHERE t.kode_token = p_kode
      AND t.digunakan_oleh IS NULL
      AND t.kedaluwarsa_pada > NOW()
      AND s.status = 'dibuka';

    RETURN IF(v_jumlah > 0, 1, 0);
END
SQL);
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        foreach (['fn_persentase_kehadiran', 'fn_jumlah_presensi', 'fn_token_valid'] as $f) {
            DB::unprepared("DROP FUNCTION IF EXISTS {$f}");
        }
    }
};
