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
        'trg_kandidat_cegah_ganda_insert',
        'trg_kandidat_cegah_ganda_update',
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
CREATE TRIGGER trg_kandidat_cegah_ganda_insert
BEFORE INSERT ON kandidat
FOR EACH ROW
BEGIN
    DECLARE v_bentrok INT DEFAULT 0;

    SELECT COUNT(*) INTO v_bentrok
    FROM kandidat
    WHERE id_pemilihan = NEW.id_pemilihan
      AND (
            id_siswa_ketua = NEW.id_siswa_ketua
         OR (NEW.id_siswa_wakil IS NOT NULL AND id_siswa_ketua = NEW.id_siswa_wakil)
         OR id_siswa_wakil = NEW.id_siswa_ketua
         OR (NEW.id_siswa_wakil IS NOT NULL AND id_siswa_wakil = NEW.id_siswa_wakil)
      );

    IF v_bentrok > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Siswa sudah terdaftar sebagai ketua atau wakil pada kandidat lain di pemilihan ini.';
    END IF;

    IF NEW.id_siswa_wakil IS NOT NULL AND NEW.id_siswa_wakil = NEW.id_siswa_ketua THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ketua dan wakil tidak boleh orang yang sama.';
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_kandidat_cegah_ganda_update
BEFORE UPDATE ON kandidat
FOR EACH ROW
BEGIN
    DECLARE v_bentrok INT DEFAULT 0;

    SELECT COUNT(*) INTO v_bentrok
    FROM kandidat
    WHERE id_pemilihan = NEW.id_pemilihan
      AND id_kandidat <> NEW.id_kandidat
      AND (
            id_siswa_ketua = NEW.id_siswa_ketua
         OR (NEW.id_siswa_wakil IS NOT NULL AND id_siswa_ketua = NEW.id_siswa_wakil)
         OR id_siswa_wakil = NEW.id_siswa_ketua
         OR (NEW.id_siswa_wakil IS NOT NULL AND id_siswa_wakil = NEW.id_siswa_wakil)
      );

    IF v_bentrok > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Siswa sudah terdaftar sebagai ketua atau wakil pada kandidat lain di pemilihan ini.';
    END IF;

    IF NEW.id_siswa_wakil IS NOT NULL AND NEW.id_siswa_wakil = NEW.id_siswa_ketua THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ketua dan wakil tidak boleh orang yang sama.';
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
