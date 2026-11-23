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

        foreach (['sp_buka_sesi', 'sp_buat_token_presensi', 'sp_scan_presensi', 'sp_tutup_sesi'] as $p) {
            DB::unprepared("DROP PROCEDURE IF EXISTS {$p}");
        }


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


        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_buat_token_presensi(
    IN p_id_sesi INT,
    IN p_id_guru INT,
    IN p_kode VARCHAR(64),
    IN p_durasi_detik INT,
    OUT p_status VARCHAR(30)
)
BEGIN
    DECLARE v_status VARCHAR(10) DEFAULT NULL;

    SELECT sp.status INTO v_status
    FROM sesi_presensi sp
    JOIN jadwal_pelajaran jp ON jp.id_jadwal = sp.id_jadwal
    WHERE sp.id_sesi = p_id_sesi AND jp.id_guru = p_id_guru;

    IF v_status IS NULL THEN
        SET p_status = 'SESI_TIDAK_VALID';
    ELSEIF v_status <> 'dibuka' THEN
        SET p_status = 'SESI_DITUTUP';
    ELSE
        INSERT INTO token_presensi (id_sesi, kode_token, dibuat_pada, kedaluwarsa_pada)
        VALUES (p_id_sesi, p_kode, NOW(), DATE_ADD(NOW(), INTERVAL p_durasi_detik SECOND));

        SET p_status = 'BERHASIL';
    END IF;
END
SQL);




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

    SELECT t.id_token, t.id_sesi, s.status, j.id_kelas
    INTO v_id_token, v_id_sesi, v_status_sesi, v_id_kelas
    FROM token_presensi t
    JOIN sesi_presensi s ON s.id_sesi = t.id_sesi
    JOIN jadwal_pelajaran j ON j.id_jadwal = s.id_jadwal
    WHERE t.kode_token = p_kode
    LIMIT 1;

    IF v_id_token IS NULL THEN
        SET p_status = 'TOKEN_TIDAK_VALID';
    ELSEIF v_status_sesi <> 'dibuka' THEN
        SET p_status = 'SESI_DITUTUP';
    ELSEIF NOT EXISTS (
        SELECT 1 FROM anggota_kelas WHERE id_kelas = v_id_kelas AND id_siswa = p_id_siswa
    ) THEN
        SET p_status = 'BUKAN_ANGGOTA_KELAS';
    ELSEIF EXISTS (
        SELECT 1 FROM presensi WHERE id_sesi = v_id_sesi AND id_siswa = p_id_siswa
    ) THEN
        SET p_status = 'SUDAH_PRESENSI';
    ELSE
        -- Satu UPDATE atomik: hanya berhasil jika token belum dipakai dan belum kedaluwarsa
        UPDATE token_presensi
        SET digunakan_oleh = p_id_siswa, digunakan_pada = NOW()
        WHERE id_token = v_id_token
          AND digunakan_oleh IS NULL
          AND kedaluwarsa_pada > NOW();

        IF ROW_COUNT() = 0 THEN
            SET p_status = 'TOKEN_KEDALUWARSA_ATAU_TERPAKAI';
        ELSE
            INSERT INTO presensi (id_sesi, id_siswa, status, metode, waktu_presensi, id_token)
            VALUES (v_id_sesi, p_id_siswa, 'hadir', 'barcode', NOW(), v_id_token);

            SET p_status = 'BERHASIL';
        END IF;
    END IF;
END
SQL);


        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_tutup_sesi(
    IN p_id_sesi INT,
    IN p_id_guru INT,
    OUT p_status VARCHAR(30),
    OUT p_jumlah_otomatis INT
)
BEGIN
    DECLARE v_status VARCHAR(10) DEFAULT NULL;
    DECLARE v_id_kelas INT DEFAULT NULL;
    DECLARE v_tanggal DATE DEFAULT NULL;

    SET p_jumlah_otomatis = 0;

    SELECT sp.status, jp.id_kelas, sp.tanggal
    INTO v_status, v_id_kelas, v_tanggal
    FROM sesi_presensi sp
    JOIN jadwal_pelajaran jp ON jp.id_jadwal = sp.id_jadwal
    WHERE sp.id_sesi = p_id_sesi AND jp.id_guru = p_id_guru;

    IF v_status IS NULL THEN
        SET p_status = 'SESI_TIDAK_VALID';
    ELSEIF v_status = 'ditutup' THEN
        SET p_status = 'SUDAH_DITUTUP';
    ELSE
        UPDATE sesi_presensi
        SET status = 'ditutup', ditutup_pada = NOW()
        WHERE id_sesi = p_id_sesi;

        INSERT INTO presensi (id_sesi, id_siswa, status, metode, id_pengajuan, catatan)
        SELECT p_id_sesi,
               ak.id_siswa,
               COALESCE(pz.jenis, 'alpa'),
               'manual',
               pz.id_pengajuan,
               IF(pz.id_pengajuan IS NULL, 'Otomatis alpa saat sesi ditutup', 'Otomatis dari pengajuan izin')
        FROM anggota_kelas ak
        LEFT JOIN pengajuan_izin pz
               ON pz.id_pengajuan = (
                    SELECT MAX(x.id_pengajuan)
                    FROM pengajuan_izin x
                    WHERE x.id_siswa = ak.id_siswa
                      AND x.status = 'disetujui'
                      AND v_tanggal BETWEEN x.tanggal_mulai AND x.tanggal_selesai
                  )
        WHERE ak.id_kelas = v_id_kelas
          AND NOT EXISTS (
                SELECT 1 FROM presensi pr
                WHERE pr.id_sesi = p_id_sesi AND pr.id_siswa = ak.id_siswa
              );

        SET p_jumlah_otomatis = ROW_COUNT();
        SET p_status = 'BERHASIL';
    END IF;
END
SQL);
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        foreach (['sp_buka_sesi', 'sp_buat_token_presensi', 'sp_scan_presensi', 'sp_tutup_sesi'] as $p) {
            DB::unprepared("DROP PROCEDURE IF EXISTS {$p}");
        }
    }
};
