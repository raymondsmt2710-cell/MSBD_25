<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

// Membatasi jam buka sesi presensi dan jam berlaku QR.
//
// Dua jendela waktu yang BERBEDA, dan perbedaannya disengaja:
//
//   1. Guru boleh membuka sesi mulai 15 menit SEBELUM jam pelajaran, sampai
//      jam pelajaran selesai. Tujuannya memberi waktu menyiapkan layar QR
//      sebelum siswa masuk.
//
//   2. QR hanya berlaku DI DALAM jam pelajaran. Meski guru membuka lebih awal,
//      siswa belum bisa memindai sampai jam_mulai tiba. Tanpa batas bawah ini,
//      presensi bisa dicatat sebelum pelajaran dimulai.
//
// Karena itu token_presensi mendapat kolom baru `berlaku_mulai`. Sebelumnya
// hanya ada kedaluwarsa_pada, sehingga tidak ada cara menyatakan "belum boleh
// dipakai".
return new class extends Migration
{
    /** Kelonggaran bagi guru membuka sesi sebelum jam pelajaran. */
    private const MENIT_SEBELUM = 15;

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

        // Kolom batas bawah masa berlaku token.
        //
        // Diisi dengan jam_mulai jadwal saat token dibuat. Baris lama (dibuat
        // sebelum migrasi ini) diisi dari jadwalnya masing-masing agar tidak
        // dianggap "belum berlaku selamanya".
        Schema::table('token_presensi', function (Blueprint $table) {
            $table->dateTime('berlaku_mulai')->nullable()->after('kode_token');
        });

        DB::unprepared(<<<'SQL'
UPDATE token_presensi t
JOIN sesi_presensi s ON s.id_sesi = t.id_sesi
JOIN jadwal_pelajaran j ON j.id_jadwal = s.id_jadwal
SET t.berlaku_mulai = TIMESTAMP(s.tanggal, j.jam_mulai)
WHERE t.berlaku_mulai IS NULL
SQL);

        // Baris yang tidak punya jadwal sah: anggap sudah berlaku sejak dibuat.
        DB::unprepared('UPDATE token_presensi SET berlaku_mulai = dibuat_pada WHERE berlaku_mulai IS NULL');

        $this->buatUlangBukaSesi();
        $this->buatUlangScan();
        $this->buatUlangTokenValid();
        $this->beriHak();
    }

    /**
     * sp_buka_sesi: cek hari DAN jam.
     *
     * Jendela buka: jam_mulai - 15 menit s/d jam_selesai.
     * Di luar itu sesi tidak boleh dibuat.
     */
    private function buatUlangBukaSesi(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_buka_sesi');

        $menit = self::MENIT_SEBELUM;

        DB::unprepared(<<<SQL
CREATE PROCEDURE sp_buka_sesi(
    IN p_id_jadwal INT,
    IN p_id_guru INT,
    OUT p_status VARCHAR(30),
    OUT p_id_sesi INT
)
BEGIN
    DECLARE v_pemilik INT DEFAULT NULL;
    DECLARE v_hari VARCHAR(10) DEFAULT NULL;
    DECLARE v_jam_mulai TIME DEFAULT NULL;
    DECLARE v_jam_selesai TIME DEFAULT NULL;
    DECLARE v_sesi INT DEFAULT NULL;

    SELECT id_guru, hari, jam_mulai, jam_selesai
    INTO v_pemilik, v_hari, v_jam_mulai, v_jam_selesai
    FROM jadwal_pelajaran
    WHERE id_jadwal = p_id_jadwal;

    IF v_pemilik IS NULL OR v_pemilik <> p_id_guru THEN
        SET p_status = 'JADWAL_TIDAK_VALID';
        SET p_id_sesi = NULL;
    ELSEIF v_hari <> ELT(WEEKDAY(CURDATE()) + 1, 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu') THEN
        SET p_status = 'BUKAN_HARI_JADWAL';
        SET p_id_sesi = NULL;
    -- Jendela buka: 15 menit sebelum jam_mulai s/d jam_selesai.
    --
    -- Kasus jadwal yang melewati tengah malam (mis. 23:30-00:30) tidak dibahas
    -- di sini: jendela dihitung pada tanggal sesi, sehingga jam_selesai yang
    -- lebih kecil dari jam_mulai akan ditolak. Jadwal sekolah tidak melewati
    -- tengah malam, jadi kasus itu sengaja tidak ditangani.
    ELSEIF CURTIME() < SUBTIME(v_jam_mulai, SEC_TO_TIME({$menit} * 60)) THEN
        SET p_status = 'TERLALU_AWAL';
        SET p_id_sesi = NULL;
    ELSEIF CURTIME() > v_jam_selesai THEN
        SET p_status = 'DI_LUAR_JAM';
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
    }

    /**
     * sp_scan_presensi: tolak pemindaian sebelum jam pelajaran dimulai.
     *
     * Pesan dibedakan dari "kedaluwarsa" supaya siswa tahu harus menunggu,
     * bukan mengira QR-nya salah.
     */
    private function buatUlangScan(): void
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
    DECLARE v_berlaku_mulai DATETIME DEFAULT NULL;

    SELECT t.id_token, t.id_sesi, s.status, j.id_kelas,
           t.kedaluwarsa_pada, t.berlaku_mulai
    INTO v_id_token, v_id_sesi, v_status_sesi, v_id_kelas,
         v_kedaluwarsa, v_berlaku_mulai
    FROM token_presensi t
    JOIN sesi_presensi s ON s.id_sesi = t.id_sesi
    JOIN jadwal_pelajaran j ON j.id_jadwal = s.id_jadwal
    WHERE t.kode_token = p_kode COLLATE utf8mb4_unicode_ci
    LIMIT 1;

    IF v_id_token IS NULL THEN
        SET p_status = 'TOKEN_TIDAK_VALID';
    ELSEIF v_status_sesi <> 'dibuka' THEN
        SET p_status = 'SESI_DITUTUP';
    -- Belum jam pelajaran: guru sudah membuka, tetapi siswa belum boleh absen.
    ELSEIF v_berlaku_mulai IS NOT NULL AND NOW() < v_berlaku_mulai THEN
        SET p_status = 'TOKEN_BELUM_BERLAKU';
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

    /** fn_token_valid: dipakai halaman konfirmasi siswa sebelum menekan "Saya Hadir". */
    private function buatUlangTokenValid(): void
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
      AND (t.berlaku_mulai IS NULL OR t.berlaku_mulai <= NOW())
      AND s.status = 'dibuka';

    RETURN IF(v_jumlah > 0, 1, 0);
END
SQL);
    }

    private function beriHak(): void
    {
        $db = config('database.connections.mysql.database');
        $host = $this->userHost();
        $siswa = env('DB_USERNAME_SISWA', 'siswa_app');
        $guru = env('DB_USERNAME_GURU', 'guru_app');

        // DROP PROCEDURE/FUNCTION menghapus hak EXECUTE, jadi diberikan lagi.
        // 1044/1142/1227 = tidak punya hak GRANT; 1133 = user tidak ada; 1410 = sudah ada.
        $aman = [1044, 1133, 1142, 1227, 1410];

        foreach ([
            "GRANT EXECUTE ON PROCEDURE `{$db}`.`sp_buka_sesi` TO '{$guru}'@'{$host}'",
            "GRANT EXECUTE ON PROCEDURE `{$db}`.`sp_scan_presensi` TO '{$siswa}'@'{$host}'",
            "GRANT EXECUTE ON FUNCTION `{$db}`.`fn_token_valid` TO '{$siswa}'@'{$host}'",
        ] as $sql) {
            try {
                DB::unprepared($sql);
            } catch (\Throwable $e) {
                $kode = (int) ($e->errorInfo[1] ?? 0);
                if (! in_array($kode, $aman, true)) {
                    throw $e;
                }
                Log::warning("Migrasi 000039 melewati GRANT. Kode {$kode}: ".$e->getMessage());
            }
        }

        try {
            DB::unprepared('FLUSH PRIVILEGES');
        } catch (\Throwable $e) {
            Log::warning('Migrasi 000039: FLUSH PRIVILEGES dilewati. '.$e->getMessage());
        }
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        Schema::table('token_presensi', function (Blueprint $table) {
            $table->dropColumn('berlaku_mulai');
        });

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_buka_sesi');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_scan_presensi');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_token_valid');
    }
};
