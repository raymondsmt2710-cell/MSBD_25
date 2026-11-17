<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

// Mencatat lokasi siswa saat memindai QR. Bersifat informasi/bukti untuk guru,
// BUKAN pembatas: presensi tetap dicatat walau lokasi ditolak atau tidak tersedia.
// GPS dapat dipalsukan dan sering meleset di dalam ruangan, sehingga tidak dipakai
// sebagai syarat sahnya presensi.
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
            $table->decimal('latitude', 10, 7)->nullable()->after('waktu_presensi');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('akurasi_meter', 8, 2)->nullable()->after('longitude');
        });

        $this->buatUlangProcedureScan();
        $this->beriHak();
    }

    /**
     * sp_scan_presensi menerima koordinat tambahan. Koordinat boleh NULL
     * (siswa menolak izin lokasi) dan tidak pernah menggagalkan presensi.
     */
    private function buatUlangProcedureScan(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_scan_presensi');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_scan_presensi(
    IN p_kode VARCHAR(64),
    IN p_id_siswa INT,
    IN p_latitude DECIMAL(10,7),
    IN p_longitude DECIMAL(10,7),
    IN p_akurasi DECIMAL(8,2),
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
        INSERT INTO presensi (
            id_sesi, id_siswa, status, metode, waktu_presensi,
            latitude, longitude, akurasi_meter, id_token
        )
        VALUES (
            v_id_sesi, p_id_siswa, 'hadir', 'barcode', NOW(),
            p_latitude, p_longitude, p_akurasi, v_id_token
        );

        SET p_status = 'BERHASIL';
    END IF;
END
SQL);
    }

    private function beriHak(): void
    {
        $db = config('database.connections.mysql.database');
        $host = $this->userHost();
        $siswa = env('DB_USERNAME_SISWA', 'siswa_app');
        $guru = env('DB_USERNAME_GURU', 'guru_app');

        try {
            // DROP PROCEDURE menghapus hak EXECUTE, jadi diberikan lagi
            DB::unprepared("GRANT EXECUTE ON PROCEDURE `{$db}`.`sp_scan_presensi` TO '{$siswa}'@'{$host}'");

            // Guru perlu membaca kolom lokasi untuk meninjau kehadiran
            DB::unprepared("GRANT SELECT ON `{$db}`.`presensi` TO '{$guru}'@'{$host}'");

            DB::unprepared('FLUSH PRIVILEGES');
        } catch (\Throwable $e) {
            Log::warning('Migration 000036: gagal memberi hak akses. Pesan: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        Schema::table('presensi', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'akurasi_meter']);
        });
    }
};
