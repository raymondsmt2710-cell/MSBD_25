<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Stored Procedure pengelolaan pemilihan (materi Stored Procedure).
//
// Semua operasi tulis data pemilihan lewat procedure, sehingga dua aturan hidup di
// satu tempat dan tidak bergantung pada kerapian kode PHP:
//   1. hanya admin, atau guru yang tercatat di panitia_pemilihan untuk pemilihan itu;
//   2. data hanya boleh diubah selama status masih 'draf'.
// Trigger trg_before_pemilihan_update menjadi lapis kedua untuk aturan (2), sehingga
// tetap berlaku walau ada kode PHP yang lupa memeriksa.
//
// Setiap procedure yang mengubah status menyetel @id_user_app agar trigger audit
// mencatat siapa pelakunya.
return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private array $procedures = [
        'sp_simpan_pemilihan',
        'sp_simpan_kandidat',
        'sp_hapus_kandidat',
        'sp_daftarkan_pemilih_kelas',
        'sp_hapus_pemilih',
        'sp_tunjuk_panitia',
        'sp_buka_pemilihan',
        'sp_tutup_pemilihan',
        'sp_kembalikan_ke_draf',
    ];

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        foreach ($this->procedures as $p) {
            DB::unprepared("DROP PROCEDURE IF EXISTS {$p}");
        }

        // ---------- 1. Simpan (buat/ubah) pemilihan ----------
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_simpan_pemilihan(
    IN p_id_pemilihan INT,
    IN p_id_user INT,
    IN p_judul VARCHAR(100),
    IN p_deskripsi TEXT,
    IN p_id_organisasi INT,
    IN p_id_tahun_ajaran INT,
    IN p_waktu_mulai DATETIME,
    IN p_waktu_selesai DATETIME,
    OUT p_status VARCHAR(30),
    OUT p_id_hasil INT
)
BEGIN
    DECLARE v_role VARCHAR(20) DEFAULT NULL;
    DECLARE v_panitia INT DEFAULT 0;
    DECLARE v_status_lama VARCHAR(20) DEFAULT NULL;

    SELECT role INTO v_role FROM users WHERE id_user = p_id_user;

    IF p_id_pemilihan IS NOT NULL THEN
        SELECT status INTO v_status_lama FROM pemilihan WHERE id_pemilihan = p_id_pemilihan;
        SELECT COUNT(*) INTO v_panitia
        FROM panitia_pemilihan
        WHERE id_pemilihan = p_id_pemilihan AND id_user = p_id_user;
    END IF;

    IF v_role IS NULL OR (v_role <> 'admin' AND v_panitia = 0) THEN
        SET p_status = 'TIDAK_BERHAK';
        SET p_id_hasil = p_id_pemilihan;
    ELSEIF p_id_pemilihan IS NOT NULL AND v_status_lama IS NULL THEN
        SET p_status = 'TIDAK_DITEMUKAN';
        SET p_id_hasil = NULL;
    ELSEIF p_id_pemilihan IS NOT NULL AND v_status_lama <> 'draf' THEN
        SET p_status = 'STATUS_TIDAK_VALID';
        SET p_id_hasil = p_id_pemilihan;
    ELSEIF p_waktu_selesai IS NULL OR p_waktu_mulai IS NULL OR p_waktu_selesai <= p_waktu_mulai THEN
        SET p_status = 'WAKTU_TIDAK_VALID';
        SET p_id_hasil = p_id_pemilihan;
    ELSE
        IF p_id_pemilihan IS NULL THEN
            INSERT INTO pemilihan (
                id_organisasi, id_tahun_ajaran, judul, deskripsi,
                waktu_mulai, waktu_selesai, status, dibuat_oleh
            )
            VALUES (
                p_id_organisasi, p_id_tahun_ajaran, p_judul, p_deskripsi,
                p_waktu_mulai, p_waktu_selesai, 'draf', p_id_user
            );

            SET p_id_hasil = LAST_INSERT_ID();
        ELSE
            UPDATE pemilihan
            SET judul = p_judul,
                deskripsi = p_deskripsi,
                id_organisasi = p_id_organisasi,
                id_tahun_ajaran = p_id_tahun_ajaran,
                waktu_mulai = p_waktu_mulai,
                waktu_selesai = p_waktu_selesai
            WHERE id_pemilihan = p_id_pemilihan;

            SET p_id_hasil = p_id_pemilihan;
        END IF;

        SET p_status = 'BERHASIL';
    END IF;
END
SQL);

        // ---------- 2. Simpan (buat/ubah) kandidat ----------
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_simpan_kandidat(
    IN p_id_kandidat INT,
    IN p_id_user INT,
    IN p_id_pemilihan INT,
    IN p_nomor_urut INT,
    IN p_id_siswa_ketua INT,
    IN p_id_siswa_wakil INT,
    IN p_visi TEXT,
    IN p_misi TEXT,
    IN p_foto_url VARCHAR(255),
    OUT p_status VARCHAR(30),
    OUT p_id_hasil INT
)
BEGIN
    DECLARE v_role VARCHAR(20) DEFAULT NULL;
    DECLARE v_panitia INT DEFAULT 0;
    DECLARE v_status VARCHAR(20) DEFAULT NULL;

    SELECT role INTO v_role FROM users WHERE id_user = p_id_user;

    SELECT status INTO v_status FROM pemilihan WHERE id_pemilihan = p_id_pemilihan;

    SELECT COUNT(*) INTO v_panitia
    FROM panitia_pemilihan
    WHERE id_pemilihan = p_id_pemilihan AND id_user = p_id_user;

    IF v_role IS NULL OR (v_role <> 'admin' AND v_panitia = 0) THEN
        SET p_status = 'TIDAK_BERHAK';
        SET p_id_hasil = p_id_kandidat;
    ELSEIF v_status IS NULL THEN
        SET p_status = 'TIDAK_DITEMUKAN';
        SET p_id_hasil = NULL;
    ELSEIF v_status <> 'draf' THEN
        SET p_status = 'STATUS_TIDAK_VALID';
        SET p_id_hasil = p_id_kandidat;
    ELSEIF p_id_siswa_ketua IS NULL THEN
        SET p_status = 'STATUS_TIDAK_VALID';
        SET p_id_hasil = p_id_kandidat;
    ELSE
        IF p_id_kandidat IS NULL THEN
            INSERT INTO kandidat (
                id_pemilihan, nomor_urut, id_siswa_ketua, id_siswa_wakil,
                visi, misi, foto_url
            )
            VALUES (
                p_id_pemilihan, p_nomor_urut, p_id_siswa_ketua, p_id_siswa_wakil,
                p_visi, p_misi, p_foto_url
            );

            SET p_id_hasil = LAST_INSERT_ID();
        ELSE
            UPDATE kandidat
            SET nomor_urut = p_nomor_urut,
                id_siswa_ketua = p_id_siswa_ketua,
                id_siswa_wakil = p_id_siswa_wakil,
                visi = p_visi,
                misi = p_misi,
                foto_url = COALESCE(p_foto_url, foto_url)
            WHERE id_kandidat = p_id_kandidat AND id_pemilihan = p_id_pemilihan;

            SET p_id_hasil = p_id_kandidat;
        END IF;

        SET p_status = 'BERHASIL';
    END IF;
END
SQL);

        // ---------- 3. Hapus kandidat ----------
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_hapus_kandidat(
    IN p_id_kandidat INT,
    IN p_id_user INT,
    OUT p_status VARCHAR(30)
)
BEGIN
    DECLARE v_role VARCHAR(20) DEFAULT NULL;
    DECLARE v_panitia INT DEFAULT 0;
    DECLARE v_status VARCHAR(20) DEFAULT NULL;
    DECLARE v_id_pemilihan INT DEFAULT NULL;
    DECLARE v_suara INT DEFAULT 0;

    SELECT id_pemilihan INTO v_id_pemilihan
    FROM kandidat WHERE id_kandidat = p_id_kandidat;

    SELECT role INTO v_role FROM users WHERE id_user = p_id_user;

    IF v_id_pemilihan IS NOT NULL THEN
        SELECT status INTO v_status FROM pemilihan WHERE id_pemilihan = v_id_pemilihan;

        SELECT COUNT(*) INTO v_panitia
        FROM panitia_pemilihan
        WHERE id_pemilihan = v_id_pemilihan AND id_user = p_id_user;
    END IF;

    IF v_id_pemilihan IS NULL THEN
        SET p_status = 'TIDAK_DITEMUKAN';
    ELSEIF v_role IS NULL OR (v_role <> 'admin' AND v_panitia = 0) THEN
        SET p_status = 'TIDAK_BERHAK';
    ELSE
        SELECT COUNT(*) INTO v_suara FROM suara WHERE id_kandidat = p_id_kandidat;

        -- Alasan yang lebih spesifik diperiksa lebih dulu: "sudah ada suara" jauh
        -- lebih informatif bagi panitia daripada "status tidak valid".
        IF v_suara > 0 THEN
            SET p_status = 'SUDAH_ADA_SUARA';
        ELSEIF v_status <> 'draf' THEN
            SET p_status = 'STATUS_TIDAK_VALID';
        ELSE
            DELETE FROM kandidat WHERE id_kandidat = p_id_kandidat;
            SET p_status = 'BERHASIL';
        END IF;
    END IF;
END
SQL);

        // ---------- 4. Daftarkan seluruh siswa satu kelas ----------
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_daftarkan_pemilih_kelas(
    IN p_id_pemilihan INT,
    IN p_id_user INT,
    IN p_id_kelas INT,
    OUT p_status VARCHAR(30),
    OUT p_jumlah INT
)
BEGIN
    DECLARE v_role VARCHAR(20) DEFAULT NULL;
    DECLARE v_panitia INT DEFAULT 0;
    DECLARE v_status VARCHAR(20) DEFAULT NULL;

    SET p_jumlah = 0;

    SELECT status INTO v_status FROM pemilihan WHERE id_pemilihan = p_id_pemilihan;

    SELECT role INTO v_role FROM users WHERE id_user = p_id_user;

    SELECT COUNT(*) INTO v_panitia
    FROM panitia_pemilihan
    WHERE id_pemilihan = p_id_pemilihan AND id_user = p_id_user;

    IF v_status IS NULL THEN
        SET p_status = 'TIDAK_DITEMUKAN';
    ELSEIF v_role IS NULL OR (v_role <> 'admin' AND v_panitia = 0) THEN
        SET p_status = 'TIDAK_BERHAK';
    ELSEIF v_status <> 'draf' THEN
        SET p_status = 'STATUS_TIDAK_VALID';
    ELSE
        -- INSERT IGNORE: pemilih yang sudah terdaftar dilewati, jadi aman diulang.
        INSERT IGNORE INTO daftar_pemilih (id_pemilihan, id_siswa, sudah_memilih, waktu_memilih)
        SELECT p_id_pemilihan, ak.id_siswa, 0, NULL
        FROM anggota_kelas ak
        INNER JOIN siswa s ON s.id_siswa = ak.id_siswa
        WHERE ak.id_kelas = p_id_kelas
          AND s.status = 'aktif';

        SET p_jumlah = ROW_COUNT();
        SET p_status = 'BERHASIL';
    END IF;
END
SQL);

        // ---------- 5. Hapus satu pemilih ----------
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_hapus_pemilih(
    IN p_id_pemilihan INT,
    IN p_id_user INT,
    IN p_id_siswa INT,
    OUT p_status VARCHAR(30)
)
BEGIN
    DECLARE v_role VARCHAR(20) DEFAULT NULL;
    DECLARE v_panitia INT DEFAULT 0;
    DECLARE v_status VARCHAR(20) DEFAULT NULL;
    DECLARE v_sudah INT DEFAULT NULL;

    SELECT status INTO v_status FROM pemilihan WHERE id_pemilihan = p_id_pemilihan;

    SELECT role INTO v_role FROM users WHERE id_user = p_id_user;

    SELECT COUNT(*) INTO v_panitia
    FROM panitia_pemilihan
    WHERE id_pemilihan = p_id_pemilihan AND id_user = p_id_user;

    SELECT sudah_memilih INTO v_sudah
    FROM daftar_pemilih
    WHERE id_pemilihan = p_id_pemilihan AND id_siswa = p_id_siswa;

    IF v_status IS NULL THEN
        SET p_status = 'TIDAK_DITEMUKAN';
    ELSEIF v_role IS NULL OR (v_role <> 'admin' AND v_panitia = 0) THEN
        SET p_status = 'TIDAK_BERHAK';
    ELSEIF v_sudah IS NULL THEN
        SET p_status = 'TIDAK_DITEMUKAN';
    ELSEIF v_sudah = 1 THEN
        SET p_status = 'SUDAH_MEMILIH';
    ELSEIF v_status <> 'draf' THEN
        SET p_status = 'STATUS_TIDAK_VALID';
    ELSE
        DELETE FROM daftar_pemilih
        WHERE id_pemilihan = p_id_pemilihan AND id_siswa = p_id_siswa;

        SET p_status = 'BERHASIL';
    END IF;
END
SQL);

        // ---------- 6. Tunjuk / cabut panitia (ADMIN SAJA) ----------
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_tunjuk_panitia(
    IN p_id_pemilihan INT,
    IN p_id_user INT,
    IN p_id_user_panitia INT,
    OUT p_status VARCHAR(30)
)
BEGIN
    DECLARE v_role VARCHAR(20) DEFAULT NULL;
    DECLARE v_status VARCHAR(20) DEFAULT NULL;
    DECLARE v_role_target VARCHAR(20) DEFAULT NULL;
    DECLARE v_ada INT DEFAULT 0;

    SELECT status INTO v_status FROM pemilihan WHERE id_pemilihan = p_id_pemilihan;

    SELECT role INTO v_role FROM users WHERE id_user = p_id_user;

    SELECT role INTO v_role_target FROM users WHERE id_user = p_id_user_panitia;

    SELECT COUNT(*) INTO v_ada
    FROM panitia_pemilihan
    WHERE id_pemilihan = p_id_pemilihan AND id_user = p_id_user_panitia;

    IF v_status IS NULL THEN
        SET p_status = 'TIDAK_DITEMUKAN';
    ELSEIF v_role IS NULL OR v_role <> 'admin' THEN
        SET p_status = 'TIDAK_BERHAK';
    ELSEIF v_status <> 'draf' THEN
        SET p_status = 'STATUS_TIDAK_VALID';
    ELSEIF v_role_target IS NULL THEN
        SET p_status = 'TIDAK_DITEMUKAN';
    ELSEIF v_ada > 0 THEN
        -- Sudah menjadi panitia: panggilan kedua berarti mencabut.
        DELETE FROM panitia_pemilihan
        WHERE id_pemilihan = p_id_pemilihan AND id_user = p_id_user_panitia;

        SET p_status = 'BERHASIL';
    ELSE
        INSERT INTO panitia_pemilihan (id_pemilihan, id_user)
        VALUES (p_id_pemilihan, p_id_user_panitia);

        SET p_status = 'BERHASIL';
    END IF;
END
SQL);

        // ---------- 7. Buka pemilihan ----------
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_buka_pemilihan(
    IN p_id_pemilihan INT,
    IN p_id_user INT,
    OUT p_status VARCHAR(30)
)
BEGIN
    DECLARE v_role VARCHAR(20) DEFAULT NULL;
    DECLARE v_panitia INT DEFAULT 0;
    DECLARE v_status VARCHAR(20) DEFAULT NULL;
    DECLARE v_selesai DATETIME DEFAULT NULL;
    DECLARE v_kandidat INT DEFAULT 0;
    DECLARE v_pemilih INT DEFAULT 0;

    SELECT status, waktu_selesai INTO v_status, v_selesai
    FROM pemilihan WHERE id_pemilihan = p_id_pemilihan;

    SELECT role INTO v_role FROM users WHERE id_user = p_id_user;

    SELECT COUNT(*) INTO v_panitia
    FROM panitia_pemilihan
    WHERE id_pemilihan = p_id_pemilihan AND id_user = p_id_user;

    IF v_status IS NULL THEN
        SET p_status = 'TIDAK_DITEMUKAN';
    ELSEIF v_role IS NULL OR (v_role <> 'admin' AND v_panitia = 0) THEN
        SET p_status = 'TIDAK_BERHAK';
    ELSEIF v_status <> 'draf' THEN
        SET p_status = 'STATUS_TIDAK_VALID';
    ELSEIF v_selesai <= NOW() THEN
        SET p_status = 'WAKTU_TIDAK_VALID';
    ELSE
        SELECT COUNT(*) INTO v_kandidat FROM kandidat WHERE id_pemilihan = p_id_pemilihan;
        SELECT COUNT(*) INTO v_pemilih FROM daftar_pemilih WHERE id_pemilihan = p_id_pemilihan;

        IF v_kandidat = 0 THEN
            SET p_status = 'BELUM_ADA_KANDIDAT';
        ELSEIF v_pemilih = 0 THEN
            SET p_status = 'BELUM_ADA_PEMILIH';
        ELSE
            SET @id_user_app = p_id_user;

            UPDATE pemilihan SET status = 'berlangsung' WHERE id_pemilihan = p_id_pemilihan;

            SET p_status = 'BERHASIL';
        END IF;
    END IF;
END
SQL);

        // ---------- 8. Tutup pemilihan ----------
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_tutup_pemilihan(
    IN p_id_pemilihan INT,
    IN p_id_user INT,
    OUT p_status VARCHAR(30),
    OUT p_jumlah_suara INT
)
BEGIN
    DECLARE v_role VARCHAR(20) DEFAULT NULL;
    DECLARE v_panitia INT DEFAULT 0;
    DECLARE v_status VARCHAR(20) DEFAULT NULL;

    SET p_jumlah_suara = 0;

    SELECT status INTO v_status FROM pemilihan WHERE id_pemilihan = p_id_pemilihan;

    SELECT role INTO v_role FROM users WHERE id_user = p_id_user;

    SELECT COUNT(*) INTO v_panitia
    FROM panitia_pemilihan
    WHERE id_pemilihan = p_id_pemilihan AND id_user = p_id_user;

    IF v_status IS NULL THEN
        SET p_status = 'TIDAK_DITEMUKAN';
    ELSEIF v_role IS NULL OR (v_role <> 'admin' AND v_panitia = 0) THEN
        SET p_status = 'TIDAK_BERHAK';
    ELSEIF v_status <> 'berlangsung' THEN
        SET p_status = 'STATUS_TIDAK_VALID';
    ELSE
        SET @id_user_app = p_id_user;

        UPDATE pemilihan SET status = 'selesai' WHERE id_pemilihan = p_id_pemilihan;

        SELECT COUNT(*) INTO p_jumlah_suara FROM suara WHERE id_pemilihan = p_id_pemilihan;

        SET p_status = 'BERHASIL';
    END IF;
END
SQL);

        // ---------- 9. Kembalikan ke draf ----------
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_kembalikan_ke_draf(
    IN p_id_pemilihan INT,
    IN p_id_user INT,
    OUT p_status VARCHAR(30)
)
BEGIN
    DECLARE v_role VARCHAR(20) DEFAULT NULL;
    DECLARE v_panitia INT DEFAULT 0;
    DECLARE v_status VARCHAR(20) DEFAULT NULL;
    DECLARE v_suara INT DEFAULT 0;

    SELECT status INTO v_status FROM pemilihan WHERE id_pemilihan = p_id_pemilihan;

    SELECT role INTO v_role FROM users WHERE id_user = p_id_user;

    SELECT COUNT(*) INTO v_panitia
    FROM panitia_pemilihan
    WHERE id_pemilihan = p_id_pemilihan AND id_user = p_id_user;

    IF v_status IS NULL THEN
        SET p_status = 'TIDAK_DITEMUKAN';
    ELSEIF v_role IS NULL OR (v_role <> 'admin' AND v_panitia = 0) THEN
        SET p_status = 'TIDAK_BERHAK';
    ELSEIF v_status = 'draf' THEN
        SET p_status = 'STATUS_TIDAK_VALID';
    ELSE
        SELECT COUNT(*) INTO v_suara FROM suara WHERE id_pemilihan = p_id_pemilihan;

        IF v_suara > 0 THEN
            -- Membuka kembali pemilihan setelah ada suara berarti mengubah hasil.
            SET p_status = 'SUDAH_ADA_SUARA';
        ELSE
            SET @id_user_app = p_id_user;

            UPDATE pemilihan SET status = 'draf' WHERE id_pemilihan = p_id_pemilihan;

            SET p_status = 'BERHASIL';
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

        foreach ($this->procedures as $p) {
            DB::unprepared("DROP PROCEDURE IF EXISTS {$p}");
        }
    }
};
