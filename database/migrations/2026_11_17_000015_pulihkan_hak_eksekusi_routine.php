<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// DCL: memulihkan hak EXECUTE pada stored routine yang hilang.
//
// Latar belakang (bug yang ditemukan saat fitur 7):
// `DROP FUNCTION` / `DROP PROCEDURE` di MySQL ikut menghapus GRANT EXECUTE yang
// menempel pada routine itu. Beberapa migrasi melakukan DROP lalu CREATE ulang,
// tetapi hanya memberi ulang hak ke sebagian peran:
//
//   - 2026_11_16_000035 hanya memberi ulang fn_jumlah_presensi ke siswa_app,
//     sehingga guru_app dan ortu_app kehilangan hak → error 1370
//     ("execute command denied ... for routine fn_jumlah_presensi").
//   - fn_token_valid juga hanya diberikan ke siswa_app.
//
// Migrasi ini mengembalikan matriks hak akses ke keadaan yang dirancang di
// 2026_11_16_000033_setup_database_users_and_grants, sehingga tidak bergantung
// pada daftar salinan di tiap migrasi yang melakukan DROP.
//
// Idempoten: GRANT boleh dijalankan berulang tanpa efek samping.
return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function userHost(): string
    {
        return env('DB_USER_HOST', 'localhost');
    }

    /**
     * Matriks hak EXECUTE sesuai rancangan 000033.
     * admin_app tidak didaftarkan karena sudah memegang ALL PRIVILEGES pada
     * database aplikasi; menambahkannya hanya duplikasi.
     */
    private function matriks(): array
    {
        return [
            'guru' => [
                'PROCEDURE' => ['sp_buka_sesi', 'sp_buat_token_presensi', 'sp_tutup_sesi'],
                'FUNCTION' => ['fn_persentase_kehadiran', 'fn_jumlah_presensi'],
            ],
            'siswa' => [
                'PROCEDURE' => ['sp_scan_presensi'],
                'FUNCTION' => ['fn_token_valid', 'fn_persentase_kehadiran', 'fn_jumlah_presensi'],
            ],
            'orangtua' => [
                'PROCEDURE' => [],
                'FUNCTION' => ['fn_persentase_kehadiran', 'fn_jumlah_presensi'],
            ],
        ];
    }

    private function akun(string $peran): string
    {
        return match ($peran) {
            'guru' => env('DB_USERNAME_GURU', 'guru_app'),
            'siswa' => env('DB_USERNAME_SISWA', 'siswa_app'),
            'orangtua' => env('DB_USERNAME_ORTU', 'ortu_app'),
        };
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        // Sama seperti 000033: di lingkungan tanpa hak GRANT, langkah ini dilewati
        // dengan sengaja lewat DB_SKIP_ROLE_USERS, bukan menggagalkan deploy.
        if (filter_var(env('DB_SKIP_ROLE_USERS', false), FILTER_VALIDATE_BOOLEAN)) {
            Log::warning('Migrasi 000015 dilewati karena DB_SKIP_ROLE_USERS=true. Hak EXECUTE tidak dipulihkan.');

            return;
        }

        $db = config('database.connections.mysql.database');
        $host = $this->userHost();
        $diberikan = 0;

        foreach ($this->matriks() as $peran => $perJenis) {
            $user = $this->akun($peran);

            foreach ($perJenis as $jenis => $daftar) {
                foreach ($daftar as $nama) {
                    $sql = "GRANT EXECUTE ON {$jenis} `{$db}`.`{$nama}` TO '{$user}'@'{$host}'";

                    try {
                        DB::unprepared($sql);
                        $diberikan++;
                    } catch (QueryException $e) {
                        // 1227/1044/1142 = tidak punya hak GRANT; 1410/1305 = routine
                        // belum ada. Dicatat sebagai error agar tidak terlewat seperti
                        // kejadian pada 000035 yang hanya menulis warning.
                        $kode = (int) ($e->errorInfo[1] ?? 0);

                        if (in_array($kode, [1044, 1142, 1227, 1305, 1410], true)) {
                            Log::error("Migrasi 000015: gagal memberi EXECUTE {$jenis} {$nama} ke {$user}. Kode {$kode}: ".$e->getMessage());

                            continue;
                        }

                        throw $e;
                    }
                }
            }
        }

        DB::unprepared('FLUSH PRIVILEGES');

        Log::info("Migrasi 000015: {$diberikan} hak EXECUTE dipulihkan.");
    }

    /**
     * Tidak mencabut hak apa pun. Mencabut EXECUTE berisiko mematikan aplikasi
     * yang sedang berjalan, sedangkan migrasi ini tujuannya memulihkan, bukan
     * membatasi. Kembalikan keadaan lewat 000033 bila memang perlu.
     */
    public function down(): void
    {
        //
    }
};
