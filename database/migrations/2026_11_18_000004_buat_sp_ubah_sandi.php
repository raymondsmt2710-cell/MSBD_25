<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Ganti sandi mandiri lewat stored procedure.
//
// Kenapa tidak memberi hak UPDATE pada tabel `users` saja:
// user MySQL per peran (siswa_app, guru_app, ortu_app) sengaja hanya punya SELECT
// pada `users` (lihat rancangan 2026_11_16_000033). Memberi UPDATE akan
// mengizinkan "UPDATE users SET password_hash = ... WHERE id_user = <orang lain>"
// di tingkat database; pertahanannya tinggal kode aplikasi. Pola proyek ini untuk
// kasus seperti ini adalah procedure + GRANT EXECUTE, sama seperti sp_scan_presensi
// (siswa menulis ke presensi) dan sp_rekam_suara.
//
// Jujur soal batasnya: ini BUKAN penutup lubang yang rapat. siswa_app masih punya
// SELECT pada users (termasuk password_hash), jadi penyerang yang sudah memegang
// kredensial DB siswa_app bisa membaca hash korban lalu memanggil procedure ini
// untuk korban. Yang benar-benar dijaga di sini adalah bentuk matriks hak yang
// sudah ada: tidak ada UPDATE/INSERT/DELETE pada `users` untuk peran non-admin.
//
// PENTING: verifikasi sandi lama TIDAK dilakukan di sini. Hash bcrypt tidak bisa
// dibandingkan lewat '=' karena kolom memakai collation *_ci (case-insensitive),
// sedangkan bcrypt peka huruf besar-kecil. Aplikasi memverifikasi dengan
// Hash::check, lalu mengirim hash lama dan hash baru. Perbandingan di bawah
// memakai BINARY dan hanya berfungsi sebagai pengaman terakhir: menangkap kasus
// hash berubah di antara dua langkah (mis. sandi direset admin di saat yang sama).
//
// DEFINER sengaja tidak ditulis, mengikuti seluruh routine lain di proyek ini:
// definer mewarisi user yang menjalankan migrasi. Menulis `DEFINER=root@localhost`
// akan gagal 1227 pada deployment yang migrasinya dijalankan sebagai admin_app.
return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    /** @return array<string, string> */
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
        // 1044/1142/1227 = tidak punya hak GRANT; 1133 = user tidak ada; 1410 = hak sudah ada.
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

        // DROP ikut mencabut GRANT EXECUTE yang menempel pada routine.
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_ubah_sandi');
    }
};
