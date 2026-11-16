<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $a = $this->accounts();

        foreach ($a as [$user, $pass]) {
            DB::unprepared("CREATE USER IF NOT EXISTS '{$user}'@'localhost' IDENTIFIED BY '{$pass}'");
        }

        $grant = function (string $user, array $map) use ($db) {
            foreach ($map as $object => $privileges) {
                DB::unprepared("GRANT {$privileges} ON {$object} TO '{$user}'@'localhost'");
            }
        };
        $t = fn (string $name) => "`{$db}`.`{$name}`";
        $proc = fn (string $name) => "PROCEDURE `{$db}`.`{$name}`";
        $func = fn (string $name) => "FUNCTION `{$db}`.`{$name}`";

        // Admin: penuh, hanya pada database aplikasi
        DB::unprepared("GRANT ALL PRIVILEGES ON `{$db}`.* TO '{$a['admin'][0]}'@'localhost'");

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
            DB::unprepared("DROP USER IF EXISTS '{$user}'@'localhost'");
        }
    }
};
