<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Trigger (materi bagian 4): audit otomatis + penjaga token sekali pakai di level database.
return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private array $triggers = [
        'trg_after_presensi_insert',
        'trg_after_presensi_update',
        'trg_before_token_update',
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
CREATE TRIGGER trg_after_presensi_insert
AFTER INSERT ON presensi
FOR EACH ROW
BEGIN
    INSERT INTO log_presensi (id_presensi, id_sesi, id_siswa, aksi, status_lama, status_baru, keterangan, dicatat_pada)
    VALUES (
        NEW.id_presensi, NEW.id_sesi, NEW.id_siswa, 'PRESENSI_BARU', NULL, NEW.status,
        CONCAT('Presensi ', NEW.status, ' melalui metode ', NEW.metode),
        NOW()
    );
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_after_presensi_update
AFTER UPDATE ON presensi
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        INSERT INTO log_presensi (id_presensi, id_sesi, id_siswa, aksi, status_lama, status_baru, keterangan, dicatat_pada)
        VALUES (
            NEW.id_presensi, NEW.id_sesi, NEW.id_siswa, 'STATUS_BERUBAH', OLD.status, NEW.status,
            CONCAT('Status berubah dari [', OLD.status, '] menjadi [', NEW.status, ']'),
            NOW()
        );
    END IF;
END
SQL);

        // Token yang sudah dipakai tidak boleh dipakai ulang / dialihkan ke siswa lain
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_token_update
BEFORE UPDATE ON token_presensi
FOR EACH ROW
BEGIN
    IF OLD.digunakan_oleh IS NOT NULL
       AND (NEW.digunakan_oleh IS NULL OR NEW.digunakan_oleh <> OLD.digunakan_oleh) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Token presensi sudah digunakan dan tidak dapat dipakai ulang';
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
