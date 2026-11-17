<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Memulihkan trg_before_suara_delete yang dibutuhkan agar suara tidak dapat dihapus.
//
// Latar: saat menyiapkan data demo, trigger ini sempat di-drop manual untuk
// mengosongkan tabel `suara`. Migration ini mengembalikannya secara idempoten,
// sehingga kondisi database selalu kembali ke aturan yang benar.
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

        DB::unprepared('DROP TRIGGER IF EXISTS trg_before_suara_delete');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_before_suara_delete
BEFORE DELETE ON suara
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Suara tidak dapat dihapus.';
END
SQL);
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS trg_before_suara_delete');
    }
};
