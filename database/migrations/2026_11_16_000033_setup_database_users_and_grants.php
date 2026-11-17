<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// DCL (materi Manajemen User & Hak Akses): user MySQL per role dengan prinsip least privilege.
// Siswa TIDAK punya INSERT ke tabel presensi; ia hanya boleh EXECUTE sp_scan_presensi.
return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function accounts(): array
    {
        return [
            'admin'    => [env('DB_USERNAME_ADMIN', 'admin_app'),       env('DB_PASSWORD_ADMIN', 'admin_app')],
            'guru'     => [env('DB_USERNAME_GURU', 'guru_app'),         env('DB_PASSWORD_GURU', 'guru_app')],
            'siswa'    => [env('DB_USERNAME_SISWA', 'siswa_app'),       env('DB_PASSWORD_SISWA', 'siswa_app')],
            'orangtua' => [env('DB_USERNAME_ORTU', 'ortu_app'),         env('DB_PASSWORD_ORTU', 'ortu_app')],
        ];
    }

    /**
     * Host pada akun MySQL. Nilai bawaan 'localhost' hanya bisa login dari mesin yang sama
     * dengan server MySQL. Set DB_USER_HOST=% (atau IP server aplikasi) bila database
     * berada di mesin terpisah, misalnya saat deploy.
     */
    private function userHost(): string
    {
        return env('DB_USER_HOST', 'localhost');
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        // Di shared hosting / akun DB tanpa hak CREATE USER & GRANT, pembuatan user per role
        // tidak mungkin dilakukan. Migration dilewati dengan peringatan, bukan menggagalkan deploy.
        // Set DB_SKIP_ROLE_USERS=true untuk melewati secara sengaja.
        if (filter_var(env('DB_SKIP_ROLE_USERS', false), FILTER_VALIDATE_BOOLEAN)) {
            Log::warning('Migration 000033 dilewati karena DB_SKIP_ROLE_USERS=true. Hak akses per role tidak dibuat.');

            return;
        }

        $db = config('database.connections.mysql.database');
        $a = $this->accounts();
        $host = $this->userHost();

        try {
            foreach ($a as [$user, $pass]) {
                DB::unprepared("CREATE USER IF NOT EXISTS '{$user}'@'{$host}' IDENTIFIED BY '{$pass}'");
            }

            $this->beriHak($a, $db, $host);
        } catch (QueryException $e) {
            // 1227 = butuh hak CREATE USER/SUPER, 1044/1142 = akses ditolak
            if (in_array((int) ($e->errorInfo[1] ?? 0), [1044, 1142, 1227], true)) {
                Log::warning(
                    'Migration 000033 tidak dapat membuat user MySQL (hak CREATE USER/GRANT tidak tersedia). '.
                    'Jalankan migration sebagai root, atau buat user & GRANT secara manual. Pesan: '.$e->getMessage()
                );

                return;
            }

            throw $e;
        }
    }

    private function beriHak(array $a, string $db, string $host): void
    {
        $grant = function (string $user, array $map) use ($db, $host) {
            foreach ($map as $object => $privileges) {
                // Toleran: objek bisa belum ada karena urutan migrasi (grant ulang
                // diberikan oleh migration yang membuat objek tersebut).
                try {
                    DB::unprepared("GRANT {$privileges} ON {$object} TO '{$user}'@'{$host}'");
                } catch (\Throwable $e) {
                    Log::warning("Lewati GRANT {$privileges} ON {$object} untuk {$user}: ".$e->getMessage());
                }
            }
        };
        $t = fn (string $name) => "`{$db}`.`{$name}`";
        $proc = fn (string $name) => "PROCEDURE `{$db}`.`{$name}`";
        $func = fn (string $name) => "FUNCTION `{$db}`.`{$name}`";

        // Admin: penuh, hanya pada database aplikasi
        DB::unprepared("GRANT ALL PRIVILEGES ON `{$db}`.* TO '{$a['admin'][0]}'@'{$host}'");

        // Guru
        $grant($a['guru'][0], [
            $t('users') => 'SELECT',
            $t('guru') => 'SELECT',
            $t('siswa') => 'SELECT',
            $t('kelas') => 'SELECT',
            $t('anggota_kelas') => 'SELECT',
            $t('mata_pelajaran') => 'SELECT',
            $t('jadwal_pelajaran') => 'SELECT',
            $t('sesi_presensi') => 'SELECT',
            $t('token_presensi') => 'SELECT',
            $t('presensi') => 'SELECT, INSERT, UPDATE',   // koreksi manual oleh guru
            $t('pengajuan_izin') => 'SELECT',
            $t('log_presensi') => 'SELECT',
            $t('v_rekap_presensi_siswa') => 'SELECT',
            $t('v_rekap_sesi_presensi') => 'SELECT',
            $proc('sp_buka_sesi') => 'EXECUTE',
            $proc('sp_buat_token_presensi') => 'EXECUTE',
            $proc('sp_tutup_sesi') => 'EXECUTE',
            $func('fn_persentase_kehadiran') => 'EXECUTE',
            $func('fn_jumlah_presensi') => 'EXECUTE',
        ]);

        // Siswa
        $grant($a['siswa'][0], [
            $t('users') => 'SELECT',
            $t('siswa') => 'SELECT',
            $t('v_rekap_presensi_siswa') => 'SELECT',
            $proc('sp_scan_presensi') => 'EXECUTE',
            $func('fn_token_valid') => 'EXECUTE',
            $func('fn_persentase_kehadiran') => 'EXECUTE',
            $func('fn_jumlah_presensi') => 'EXECUTE',
        ]);

        // Orang tua / wali: hanya-baca
        $grant($a['orangtua'][0], [
            $t('users') => 'SELECT',
            $t('orang_tua') => 'SELECT',
            $t('orang_tua_siswa') => 'SELECT',
            $t('siswa') => 'SELECT',
            $t('v_rekap_presensi_siswa') => 'SELECT',
            $func('fn_persentase_kehadiran') => 'EXECUTE',
            $func('fn_jumlah_presensi') => 'EXECUTE',
        ]);

        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        foreach ($this->accounts() as [$user]) {
            DB::unprepared("DROP USER IF EXISTS '{$user}'@'{$this->userHost()}'");
        }
    }
};
