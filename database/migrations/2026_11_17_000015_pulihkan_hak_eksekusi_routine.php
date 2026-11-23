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

    private function userHost(): string
    {
        return env('DB_USER_HOST', 'localhost');
    }


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


    public function down(): void
    {

    }
};
