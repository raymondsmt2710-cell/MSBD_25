<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Perbaikan bug collation pada fitur presensi (ditemukan saat menguji fitur pemilihan).
//
// Masalah: parameter Stored Function mewarisi collation DEFAULT DATABASE
// (utf8mb4_0900_ai_ci pada MySQL 8.4), sedangkan kolom `presensi.status` adalah ENUM
// dengan collation utf8mb4_unicode_ci (dari config/database.php). Perbandingan
// ENUM vs parameter berbeda collation menghasilkan:
//   SQLSTATE[HY000] 1267 Illegal mix of collations ... for operation '='
//
// Akibatnya fn_jumlah_presensi() SELALU gagal, sehingga halaman riwayat presensi siswa
// (tujuan redirect setelah login) error 500 dan siswa tidak bisa masuk.
//
// Perbaikan: tulis COLLATE eksplisit pada perbandingan di dalam function. Ini tidak
// mengubah nilai apa pun, hanya cara MySQL membandingkan teks.
//
// fn_token_valid() tidak terpengaruh karena kolom yang dibandingkannya (kode_token)
// bertipe VARCHAR, yang bisa dikoersi otomatis; ENUM tidak bisa.
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

        DB::unprepared('DROP FUNCTION IF EXISTS fn_jumlah_presensi');

        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_jumlah_presensi(p_id_siswa INT, p_status VARCHAR(10))
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT COUNT(*) INTO v_jumlah
    FROM presensi
    WHERE id_siswa = p_id_siswa
      AND status = p_status COLLATE utf8mb4_unicode_ci;

    RETURN v_jumlah;
END
SQL);

        // DROP FUNCTION menghapus GRANT EXECUTE yang melekat pada function itu,
        // sehingga hak akses harus diberikan ulang. Tanpa ini, halaman presensi
        // siswa kembali error (1370 execute command denied).
        $db = config('database.connections.mysql.database');

        foreach ([
            env('DB_USERNAME_ADMIN', 'admin_app'),
            env('DB_USERNAME_GURU', 'guru_app'),
            env('DB_USERNAME_SISWA', 'siswa_app'),
            env('DB_USERNAME_ORTU', 'ortu_app'),
        ] as $user) {
            DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`fn_jumlah_presensi` TO '{$user}'@'localhost'");
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared('DROP FUNCTION IF EXISTS fn_jumlah_presensi');

        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_jumlah_presensi(p_id_siswa INT, p_status VARCHAR(10))
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT COUNT(*) INTO v_jumlah
    FROM presensi
    WHERE id_siswa = p_id_siswa AND status = p_status;

    RETURN v_jumlah;
END
SQL);
    }
};
