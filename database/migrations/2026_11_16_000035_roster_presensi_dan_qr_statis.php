<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function userHost(): string
    {
        return env('DB_USER_HOST', 'localhost');
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }




        Schema::table('presensi', function (Blueprint $table) {
            $table->index('id_token', 'idx_presensi_id_token');
        });

        Schema::table('presensi', function (Blueprint $table) {
            $table->dropUnique('presensi_id_token_unique');
        });

        $this->buatUlangProcedureScan();
        $this->buatUlangFungsiTokenValid();
        $this->buatUlangFungsiJumlah();
        $this->buatView();
        $this->beriHak();
    }


    private function buatUlangProcedureScan(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_scan_presensi');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_scan_presensi(
    IN p_kode VARCHAR(64),
    IN p_id_siswa INT,
    OUT p_status VARCHAR(40)
)
BEGIN
    DECLARE v_id_token INT DEFAULT NULL;
    DECLARE v_id_sesi INT DEFAULT NULL;
    DECLARE v_status_sesi VARCHAR(10) DEFAULT NULL;
    DECLARE v_id_kelas INT DEFAULT NULL;
    DECLARE v_kedaluwarsa DATETIME DEFAULT NULL;

    SELECT t.id_token, t.id_sesi, s.status, j.id_kelas, t.kedaluwarsa_pada
    INTO v_id_token, v_id_sesi, v_status_sesi, v_id_kelas, v_kedaluwarsa
    FROM token_presensi t
    JOIN sesi_presensi s ON s.id_sesi = t.id_sesi
    JOIN jadwal_pelajaran j ON j.id_jadwal = s.id_jadwal
    WHERE t.kode_token = p_kode COLLATE utf8mb4_unicode_ci
    LIMIT 1;

    IF v_id_token IS NULL THEN
        SET p_status = 'TOKEN_TIDAK_VALID';
    ELSEIF v_status_sesi <> 'dibuka' THEN
        SET p_status = 'SESI_DITUTUP';
    ELSEIF v_kedaluwarsa <= NOW() THEN
        SET p_status = 'TOKEN_KEDALUWARSA_ATAU_TERPAKAI';
    ELSEIF NOT EXISTS (
        SELECT 1 FROM anggota_kelas WHERE id_kelas = v_id_kelas AND id_siswa = p_id_siswa
    ) THEN
        SET p_status = 'BUKAN_ANGGOTA_KELAS';
    ELSEIF EXISTS (
        SELECT 1 FROM presensi WHERE id_sesi = v_id_sesi AND id_siswa = p_id_siswa
    ) THEN
        SET p_status = 'SUDAH_PRESENSI';
    ELSE
        -- Token tetap (statis) sepanjang sesi; yang dijaga agar tidak dobel adalah
        -- pasangan (id_sesi, id_siswa) lewat unique index.
        INSERT INTO presensi (id_sesi, id_siswa, status, metode, waktu_presensi, id_token)
        VALUES (v_id_sesi, p_id_siswa, 'hadir', 'barcode', NOW(), v_id_token);

        SET p_status = 'BERHASIL';
    END IF;
END
SQL);
    }


    private function buatUlangFungsiTokenValid(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS fn_token_valid');




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
    WHERE t.kode_token = p_kode COLLATE utf8mb4_unicode_ci
      AND t.kedaluwarsa_pada > NOW()
      AND s.status = 'dibuka';

    RETURN IF(v_jumlah > 0, 1, 0);
END
SQL);
    }


    private function buatUlangFungsiJumlah(): void
    {
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
    }

    private function buatView(): void
    {

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_roster_presensi_siswa AS
SELECT
    ak.id_siswa,
    jp.id_jadwal,
    jp.id_kelas,
    k.nama_kelas,
    jp.id_guru,
    g.nama_guru,
    jp.id_mapel,
    m.nama_mapel,
    jp.hari,
    jp.jam_mulai,
    jp.jam_selesai
FROM anggota_kelas ak
INNER JOIN jadwal_pelajaran jp ON jp.id_kelas = ak.id_kelas
INNER JOIN kelas k ON k.id_kelas = jp.id_kelas
INNER JOIN mata_pelajaran m ON m.id_mapel = jp.id_mapel
INNER JOIN guru g ON g.id_guru = jp.id_guru
SQL);



        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_sesi_presensi_siswa AS
SELECT
    ak.id_siswa,
    sp.id_sesi,
    sp.id_jadwal,
    sp.tanggal,
    sp.status AS status_sesi,
    p.id_presensi,
    p.status AS status_presensi,
    p.metode,
    p.waktu_presensi
FROM sesi_presensi sp
INNER JOIN jadwal_pelajaran jp ON jp.id_jadwal = sp.id_jadwal
INNER JOIN anggota_kelas ak ON ak.id_kelas = jp.id_kelas
LEFT JOIN presensi p ON p.id_sesi = sp.id_sesi AND p.id_siswa = ak.id_siswa
SQL);
    }

    private function beriHak(): void
    {
        $db = config('database.connections.mysql.database');
        $host = $this->userHost();
        $siswa = env('DB_USERNAME_SISWA', 'siswa_app');
        $guru = env('DB_USERNAME_GURU', 'guru_app');

        try {

            DB::unprepared("GRANT SELECT ON `{$db}`.`v_roster_presensi_siswa` TO '{$siswa}'@'{$host}'");
            DB::unprepared("GRANT SELECT ON `{$db}`.`v_sesi_presensi_siswa` TO '{$siswa}'@'{$host}'");


            DB::unprepared("GRANT EXECUTE ON PROCEDURE `{$db}`.`sp_scan_presensi` TO '{$siswa}'@'{$host}'");
            DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`fn_token_valid` TO '{$siswa}'@'{$host}'");
            DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`fn_jumlah_presensi` TO '{$siswa}'@'{$host}'");


            DB::unprepared("GRANT SELECT ON `{$db}`.`v_sesi_presensi_siswa` TO '{$guru}'@'{$host}'");

            DB::unprepared('FLUSH PRIVILEGES');
        } catch (\Throwable $e) {
            Log::warning('Migration 000035: gagal memberi hak akses. Pesan: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared('DROP VIEW IF EXISTS v_sesi_presensi_siswa');
        DB::unprepared('DROP VIEW IF EXISTS v_roster_presensi_siswa');


        Schema::table('presensi', function (Blueprint $table) {
            $table->unique('id_token');
        });

        Schema::table('presensi', function (Blueprint $table) {
            $table->dropIndex('idx_presensi_id_token');
        });
    }
};
