<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Trigger + index untuk fitur perangkat organisasi & kelas.
//
// Trigger menjaga integritas data pengurus di level database, sehingga aturan
// tetap berlaku walaupun ada jalur tulis lain (procedure, seeder, kode baru):
//   1. Siswa pada pengurus_kelas WAJIB anggota kelas tersebut.
//   2. Jabatan pada pengurus_kelas WAJIB berlingkup 'kelas'.
//   3. Jabatan pada pengurus_organisasi WAJIB berlingkup 'organisasi'.
//   4. Satu siswa tidak boleh memegang dua jabatan pada kelas yang sama
//      (dan pada organisasi+tahun ajaran yang sama).
return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private array $triggers = [
        'trg_pengurus_kelas_insert',
        'trg_pengurus_kelas_update',
        'trg_pengurus_organisasi_insert',
        'trg_pengurus_organisasi_update',
    ];

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        foreach ($this->triggers as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}");
        }

        // ---------- pengurus_kelas ----------

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_pengurus_kelas_insert
BEFORE INSERT ON pengurus_kelas
FOR EACH ROW
BEGIN
    DECLARE v_lingkup VARCHAR(20) DEFAULT NULL;
    DECLARE v_anggota INT DEFAULT 0;
    DECLARE v_sudah INT DEFAULT 0;

    SELECT lingkup INTO v_lingkup FROM jabatan WHERE id_jabatan = NEW.id_jabatan;

    IF v_lingkup IS NULL OR v_lingkup <> 'kelas' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Jabatan tersebut bukan jabatan kelas.';
    END IF;

    SELECT COUNT(*) INTO v_anggota
    FROM anggota_kelas
    WHERE id_kelas = NEW.id_kelas AND id_siswa = NEW.id_siswa;

    IF v_anggota = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Siswa bukan anggota kelas tersebut.';
    END IF;

    SELECT COUNT(*) INTO v_sudah
    FROM pengurus_kelas
    WHERE id_kelas = NEW.id_kelas AND id_siswa = NEW.id_siswa;

    IF v_sudah > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Siswa sudah memegang jabatan lain di kelas ini.';
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_pengurus_kelas_update
BEFORE UPDATE ON pengurus_kelas
FOR EACH ROW
BEGIN
    DECLARE v_lingkup VARCHAR(20) DEFAULT NULL;
    DECLARE v_anggota INT DEFAULT 0;
    DECLARE v_sudah INT DEFAULT 0;

    SELECT lingkup INTO v_lingkup FROM jabatan WHERE id_jabatan = NEW.id_jabatan;

    IF v_lingkup IS NULL OR v_lingkup <> 'kelas' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Jabatan tersebut bukan jabatan kelas.';
    END IF;

    SELECT COUNT(*) INTO v_anggota
    FROM anggota_kelas
    WHERE id_kelas = NEW.id_kelas AND id_siswa = NEW.id_siswa;

    IF v_anggota = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Siswa bukan anggota kelas tersebut.';
    END IF;

    SELECT COUNT(*) INTO v_sudah
    FROM pengurus_kelas
    WHERE id_kelas = NEW.id_kelas
      AND id_siswa = NEW.id_siswa
      AND id_pengurus_kelas <> NEW.id_pengurus_kelas;

    IF v_sudah > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Siswa sudah memegang jabatan lain di kelas ini.';
    END IF;
END
SQL);

        // ---------- pengurus_organisasi ----------

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_pengurus_organisasi_insert
BEFORE INSERT ON pengurus_organisasi
FOR EACH ROW
BEGIN
    DECLARE v_lingkup VARCHAR(20) DEFAULT NULL;
    DECLARE v_sudah INT DEFAULT 0;

    SELECT lingkup INTO v_lingkup FROM jabatan WHERE id_jabatan = NEW.id_jabatan;

    IF v_lingkup IS NULL OR v_lingkup <> 'organisasi' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Jabatan tersebut bukan jabatan organisasi.';
    END IF;

    SELECT COUNT(*) INTO v_sudah
    FROM pengurus_organisasi
    WHERE id_organisasi = NEW.id_organisasi
      AND id_tahun_ajaran = NEW.id_tahun_ajaran
      AND id_siswa = NEW.id_siswa;

    IF v_sudah > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Siswa sudah memegang jabatan lain di organisasi ini pada tahun ajaran yang sama.';
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_pengurus_organisasi_update
BEFORE UPDATE ON pengurus_organisasi
FOR EACH ROW
BEGIN
    DECLARE v_lingkup VARCHAR(20) DEFAULT NULL;
    DECLARE v_sudah INT DEFAULT 0;

    SELECT lingkup INTO v_lingkup FROM jabatan WHERE id_jabatan = NEW.id_jabatan;

    IF v_lingkup IS NULL OR v_lingkup <> 'organisasi' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Jabatan tersebut bukan jabatan organisasi.';
    END IF;

    SELECT COUNT(*) INTO v_sudah
    FROM pengurus_organisasi
    WHERE id_organisasi = NEW.id_organisasi
      AND id_tahun_ajaran = NEW.id_tahun_ajaran
      AND id_siswa = NEW.id_siswa
      AND id_pengurus_org <> NEW.id_pengurus_org;

    IF v_sudah > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Siswa sudah memegang jabatan lain di organisasi ini pada tahun ajaran yang sama.';
    END IF;
END
SQL);

        // ---------- INDEX (materi Optimasi Query & Indexing) ----------
        Schema::table('pengurus_kelas', function (Blueprint $table) {
            $table->index(['id_kelas', 'id_jabatan'], 'idx_pengurus_kelas');
        });

        Schema::table('pengurus_organisasi', function (Blueprint $table) {
            $table->index(['id_organisasi', 'id_tahun_ajaran'], 'idx_pengurus_organisasi');
        });
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        Schema::table('pengurus_organisasi', fn (Blueprint $t) => $t->dropIndex('idx_pengurus_organisasi'));
        Schema::table('pengurus_kelas', fn (Blueprint $t) => $t->dropIndex('idx_pengurus_kelas'));

        foreach ($this->triggers as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}");
        }
    }
};
