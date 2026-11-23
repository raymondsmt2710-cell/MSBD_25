<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private array $triggers = [
        'trg_before_suara_insert',
        'trg_before_suara_update',
        'trg_before_suara_delete',
        'trg_before_kandidat_delete',
        'trg_before_daftar_pemilih_delete',
        'trg_before_pemilihan_update',
        'trg_after_pemilihan_update',
        'trg_after_daftar_pemilih_update',
    ];

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        foreach ($this->triggers as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}");
        }



        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_suara_insert
BEFORE INSERT ON suara
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(20) DEFAULT NULL;
    DECLARE v_milik INT DEFAULT NULL;

    SELECT status INTO v_status
    FROM pemilihan
    WHERE id_pemilihan = NEW.id_pemilihan;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pemilihan tidak ditemukan.';
    ELSEIF v_status <> 'berlangsung' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Suara hanya dapat direkam saat pemilihan berlangsung.';
    END IF;

    SELECT id_pemilihan INTO v_milik
    FROM kandidat
    WHERE id_kandidat = NEW.id_kandidat;

    IF v_milik IS NULL OR v_milik <> NEW.id_pemilihan THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Kandidat bukan milik pemilihan ini.';
    END IF;
END
SQL);


        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_suara_update
BEFORE UPDATE ON suara
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Suara tidak dapat diubah.';
END
SQL);


        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_suara_delete
BEFORE DELETE ON suara
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Suara tidak dapat dihapus.';
END
SQL);




        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_kandidat_delete
BEFORE DELETE ON kandidat
FOR EACH ROW
BEGIN
    DECLARE v_suara INT DEFAULT 0;

    SELECT COUNT(*) INTO v_suara
    FROM suara
    WHERE id_kandidat = OLD.id_kandidat;

    IF v_suara > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Kandidat sudah memiliki suara dan tidak dapat dihapus.';
    END IF;
END
SQL);



        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_daftar_pemilih_delete
BEFORE DELETE ON daftar_pemilih
FOR EACH ROW
BEGIN
    IF OLD.sudah_memilih = 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pemilih sudah memberikan suara dan tidak dapat dihapus.';
    END IF;
END
SQL);




        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_pemilihan_update
BEFORE UPDATE ON pemilihan
FOR EACH ROW
BEGIN
    IF OLD.status <> 'draf' THEN
        IF NEW.judul <> OLD.judul
           OR NEW.waktu_mulai <> OLD.waktu_mulai
           OR NEW.waktu_selesai <> OLD.waktu_selesai
           OR NEW.id_organisasi <> OLD.id_organisasi
           OR NEW.id_tahun_ajaran <> OLD.id_tahun_ajaran
           OR NOT (NEW.deskripsi <=> OLD.deskripsi) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Data pemilihan terkunci karena pemilihan sudah dibuka.';
        END IF;
    END IF;
END
SQL);


        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_after_pemilihan_update
AFTER UPDATE ON pemilihan
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        INSERT INTO log_pemilihan (
            id_pemilihan, id_user, aksi, status_lama, status_baru, keterangan, dibuat_pada
        )
        VALUES (
            NEW.id_pemilihan,
            @id_user_app,
            'STATUS_BERUBAH',
            OLD.status,
            NEW.status,
            CONCAT('Status pemilihan berubah dari [', OLD.status, '] menjadi [', NEW.status, ']'),
            NOW()
        );
    END IF;
END
SQL);



        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_after_daftar_pemilih_update
AFTER UPDATE ON daftar_pemilih
FOR EACH ROW
BEGIN
    IF OLD.sudah_memilih = 0 AND NEW.sudah_memilih = 1 THEN
        INSERT INTO log_pemilihan (
            id_pemilihan, id_user, aksi, status_lama, status_baru, keterangan, dibuat_pada
        )
        VALUES (
            NEW.id_pemilihan,
            NULL,
            'PEMILIH_MEMILIH',
            'belum',
            'sudah',
            CONCAT('Pemilih terdaftar (id_siswa ', NEW.id_siswa, ') telah menggunakan hak pilihnya.'),
            NOW()
        );
    END IF;
END
SQL);
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        foreach ($this->triggers as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}");
        }
    }
};
