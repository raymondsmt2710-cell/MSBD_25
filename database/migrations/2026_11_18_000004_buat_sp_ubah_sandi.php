<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }


    private function akun(): array
    {
        return [
            'guru' => env('DB_USERNAME_GURU', 'guru_app'),
            'siswa' => env('DB_USERNAME_SISWA', 'siswa_app'),
            'ortu' => env('DB_USERNAME_ORTU', 'ortu_app'),
        ];
    }

    private function jalankan(string $sql, array $kodeAman): void
    {
        try {
            DB::unprepared($sql);
        } catch (QueryException $e) {
            $kode = (int) ($e->errorInfo[1] ?? 0);
            if (! in_array($kode, $kodeAman, true)) {
                throw $e;
            }
            Log::error("Migrasi 2026_11_18_000004 melewati: {$sql}. Kode {$kode}: ".$e->getMessage());
        }
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_ubah_sandi');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_ubah_sandi(
    IN p_id_user INT UNSIGNED,
    IN p_sandi_lama VARCHAR(255),
    IN p_sandi_baru VARCHAR(255),
    OUT p_status VARCHAR(30)
)
BEGIN
    DECLARE v_ada INT DEFAULT 0;

    SELECT COUNT(*) INTO v_ada
    FROM users
    WHERE id_user = p_id_user;

    IF v_ada = 0 THEN
        SET p_status = 'AKUN_TIDAK_DITEMUKAN';
    ELSEIF p_sandi_baru IS NULL OR p_sandi_baru = '' THEN
        SET p_status = 'SANDI_BARU_KOSONG';
    ELSE
        -- Tulis bersyarat: hanya berhasil bila hash lama masih sama persis.
        -- BINARY wajib karena kolom password_hash memakai collation
        -- utf8mb4_unicode_ci; tanpa BINARY, dua hash yang hanya beda huruf
        -- besar-kecil dianggap sama (kelas bug yang sama dengan 001009).
        UPDATE users
        SET password_hash = p_sandi_baru
        WHERE id_user = p_id_user
          AND BINARY password_hash = BINARY p_sandi_lama;

        IF ROW_COUNT() = 0 THEN
            -- Hash berubah sejak aplikasi memverifikasi sandi lama.
            SET p_status = 'SANDI_LAMA_SALAH';
        ELSE
            SET p_status = 'BERHASIL';
        END IF;
    END IF;
END
SQL);

        if (filter_var(env('DB_SKIP_ROLE_USERS', false), FILTER_VALIDATE_BOOLEAN)) {
            Log::warning('Migrasi 2026_11_18_000004: GRANT dilewati karena DB_SKIP_ROLE_USERS=true.');

            return;
        }

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');

        $aman = [1044, 1133, 1142, 1227, 1410];

        foreach ($this->akun() as $user) {
            $this->jalankan(
                "GRANT EXECUTE ON PROCEDURE `{$db}`.`sp_ubah_sandi` TO '{$user}'@'{$host}'",
                $aman
            );
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }


        DB::unprepared('DROP PROCEDURE IF EXISTS sp_ubah_sandi');
    }
};
