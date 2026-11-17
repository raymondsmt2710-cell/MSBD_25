<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Menutup celah keabsahan suara yang ditemukan pada review akhir.
//
// Latar belakang: migration 000005 hanya menjaga `suara` dan sebagian `pemilihan`.
// Akibatnya, kredensial database per role (guru_app memegang INSERT/UPDATE/DELETE pada
// tabel pemilihan) masih bisa:
//   1. DELETE pemilihan -> cascadeOnDelete menghapus semua suara TANPA memicu
//      trg_before_suara_delete / trg_before_kandidat_delete (MySQL tidak menjalankan
//      trigger pada baris yang dihapus lewat ON DELETE CASCADE).
//   2. UPDATE daftar_pemilih SET sudah_memilih = 0 -> siswa bisa memilih dua kali.
//   3. INSERT kandidat / daftar_pemilih saat pemilihan sudah berlangsung.
//   4. Mengubah status selesai -> berlangsung, lalu menambah suara setelah hasil terbit.
//
// Trigger di bawah ini menutup keempatnya di level database, sehingga aturan tetap
// berlaku walaupun ada kode aplikasi (atau manusia dengan kredensial role) yang salah.
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

        // 1. Pemilihan yang sudah punya suara tidak boleh dihapus. Ini yang menutup
        //    jalan pintas lewat ON DELETE CASCADE.
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

        // 2. Hak pilih yang sudah dipakai tidak boleh dikembalikan. Tanpa ini, satu
        //    UPDATE sudah_memilih = 0 membuat siswa bisa memilih dua kali.
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

        // 3. Pemilih hanya boleh didaftarkan saat pemilihan masih draf.
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

        // 4. Kandidat hanya boleh ditambah saat pemilihan masih draf.
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

        // 5. Kandidat hanya boleh diubah saat pemilihan masih draf.
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

        // 6. Perketat trigger lama: `status` sebelumnya tidak dijaga sama sekali,
        //    sehingga pemilihan yang sudah `selesai` bisa dibuka lagi dan ditambah
        //    suara setelah hasil terbit. Sekarang hanya transisi berikut yang sah:
        //      draf -> berlangsung, berlangsung -> selesai, berlangsung -> draf.
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

        // Kembalikan trigger lama (tanpa penjagaan status).
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
