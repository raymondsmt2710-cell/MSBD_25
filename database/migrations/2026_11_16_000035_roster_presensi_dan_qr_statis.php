<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

// Perubahan alur presensi: satu QR statis per sesi (berlaku sampai jam tutup) dan
// view roster untuk guru & siswa.
//
// Token tidak lagi "dikonsumsi" per siswa. Satu token dipakai semua anggota kelas selama
// sesi dibuka. Keunikan presensi tetap dijaga oleh unique (id_sesi, id_siswa).
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

        // 1. id_token tidak lagi unik: satu token dipakai banyak baris presensi.
        //    Urutan penting: index biasa dibuat DULU, karena foreign key presensi_id_token_foreign
        //    membutuhkan index pada kolom id_token. Setelah itu unique-nya baru boleh dihapus.
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

    /** Token tidak lagi dikonsumsi: cukup cek masa berlaku, lalu catat presensi. */
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

    /** Token valid selama belum kedaluwarsa dan sesi masih dibuka. */
    private function buatUlangFungsiTokenValid(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS fn_token_valid');

        // COLLATE wajib: parameter function memakai collation default server
        // (utf8mb4_0900_ai_ci) sedangkan kolom kode_token memakai utf8mb4_unicode_ci.
        // Tanpa COLLATE, MySQL menolak dengan error 1267.
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

    /**
     * fn_jumlah_presensi juga perlu dibuat ulang: parameter p_status bertipe
     * VARCHAR dengan collation default server, dibandingkan dengan kolom ENUM
     * presensi.status. Tanpa COLLATE, MySQL menolak dengan error 1267.
     */
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
        // Roster siswa: seluruh jadwal kelas tempat siswa terdaftar, lengkap dengan nama guru.
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

        // Sesi milik siswa pada rentang tanggal tertentu, beserta status presensinya.
        // Dipakai roster siswa untuk menentukan kolom Status.
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
        $ortu = env('DB_USERNAME_ORTU', 'ortu_app');

        try {
            // View baru untuk siswa
            DB::unprepared("GRANT SELECT ON `{$db}`.`v_roster_presensi_siswa` TO '{$siswa}'@'{$host}'");
            DB::unprepared("GRANT SELECT ON `{$db}`.`v_sesi_presensi_siswa` TO '{$siswa}'@'{$host}'");

            // DROP PROCEDURE / DROP FUNCTION di atas menghapus hak EXECUTE yang menempel
            // pada routine itu, jadi hak harus diberikan lagi. Daftar peran di sini WAJIB
            // sama dengan rancangan 2026_11_16_000033: sempat hanya siswa yang diberi,
            // sehingga guru dan orang tua kena error 1370.
            DB::unprepared("GRANT EXECUTE ON PROCEDURE `{$db}`.`sp_scan_presensi` TO '{$siswa}'@'{$host}'");
            DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`fn_token_valid` TO '{$siswa}'@'{$host}'");
            DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`fn_jumlah_presensi` TO '{$siswa}'@'{$host}'");
            DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`fn_jumlah_presensi` TO '{$guru}'@'{$host}'");
            DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`fn_jumlah_presensi` TO '{$ortu}'@'{$host}'");

            // Guru perlu membaca view sesi untuk roster
            DB::unprepared("GRANT SELECT ON `{$db}`.`v_sesi_presensi_siswa` TO '{$guru}'@'{$host}'");

            DB::unprepared('FLUSH PRIVILEGES');
        } catch (\Throwable $e) {
            // Dinaikkan dari warning ke error: kegagalan GRANT berarti aplikasi pasti
            // rusak (error 1370), jadi tidak boleh terkubur di log level rendah.
            Log::error('Migration 000035: gagal memberi hak akses. Pesan: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared('DROP VIEW IF EXISTS v_sesi_presensi_siswa');
        DB::unprepared('DROP VIEW IF EXISTS v_roster_presensi_siswa');

        // Kembalikan unique pada id_token (hanya bisa jika tidak ada duplikat)
        Schema::table('presensi', function (Blueprint $table) {
            $table->unique('id_token');
        });

        Schema::table('presensi', function (Blueprint $table) {
            $table->dropIndex('idx_presensi_id_token');
        });
    }
};
