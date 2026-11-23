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
        'trg_before_pemilihan_delete',
        'trg_before_daftar_pemilih_update',
        'trg_before_daftar_pemilih_insert',
        'trg_before_kandidat_insert',
        'trg_before_kandidat_update',
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
CREATE TRIGGER trg_before_pemilihan_delete
BEFORE DELETE ON pemilihan
FOR EACH ROW
BEGIN
    DECLARE v_suara INT DEFAULT 0;

    SELECT COUNT(*) INTO v_suara
    FROM suara
    WHERE id_pemilihan = OLD.id_pemilihan;

    IF v_suara > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pemilihan sudah memiliki suara dan tidak dapat dihapus.';
    END IF;
END
SQL);



        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_daftar_pemilih_update
BEFORE UPDATE ON daftar_pemilih
FOR EACH ROW
BEGIN
    IF OLD.sudah_memilih = 1 AND NEW.sudah_memilih = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Hak pilih yang sudah digunakan tidak dapat dikembalikan.';
    END IF;

    IF NEW.id_pemilihan <> OLD.id_pemilihan OR NEW.id_siswa <> OLD.id_siswa THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Baris daftar pemilih tidak dapat dipindahkan.';
    END IF;
END
SQL);


        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_daftar_pemilih_insert
BEFORE INSERT ON daftar_pemilih
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(20) DEFAULT NULL;

    SELECT status INTO v_status
    FROM pemilihan
    WHERE id_pemilihan = NEW.id_pemilihan;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pemilihan tidak ditemukan.';
    ELSEIF v_status <> 'draf' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Daftar pemilih terkunci karena pemilihan sudah dibuka.';
    END IF;
END
SQL);


        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_kandidat_insert
BEFORE INSERT ON kandidat
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(20) DEFAULT NULL;

    SELECT status INTO v_status
    FROM pemilihan
    WHERE id_pemilihan = NEW.id_pemilihan;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pemilihan tidak ditemukan.';
    ELSEIF v_status <> 'draf' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Daftar kandidat terkunci karena pemilihan sudah dibuka.';
    END IF;
END
SQL);


        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_kandidat_update
BEFORE UPDATE ON kandidat
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(20) DEFAULT NULL;

    SELECT status INTO v_status
    FROM pemilihan
    WHERE id_pemilihan = OLD.id_pemilihan;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pemilihan tidak ditemukan.';
    ELSEIF v_status <> 'draf' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Daftar kandidat terkunci karena pemilihan sudah dibuka.';
    END IF;
END
SQL);





        DB::unprepared('DROP TRIGGER IF EXISTS trg_before_pemilihan_update');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_pemilihan_update
BEFORE UPDATE ON pemilihan
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        IF NOT (
            (OLD.status = 'draf' AND NEW.status = 'berlangsung')
            OR (OLD.status = 'berlangsung' AND NEW.status = 'selesai')
            OR (OLD.status = 'berlangsung' AND NEW.status = 'draf')
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Perubahan status pemilihan tidak diizinkan.';
        END IF;
    END IF;

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
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        foreach ($this->triggers as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}");
        }


        DB::unprepared('DROP TRIGGER IF EXISTS trg_before_pemilihan_update');

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
    }
};
