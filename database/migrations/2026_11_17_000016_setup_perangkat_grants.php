<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function accounts(): array
    {
        return [
            'admin'    => [env('DB_USERNAME_ADMIN', 'admin_app'),   env('DB_PASSWORD_ADMIN', 'admin_app')],
            'guru'     => [env('DB_USERNAME_GURU', 'guru_app'),     env('DB_PASSWORD_GURU', 'guru_app')],
            'siswa'    => [env('DB_USERNAME_SISWA', 'siswa_app'),   env('DB_PASSWORD_SISWA', 'siswa_app')],
            'orangtua' => [env('DB_USERNAME_ORTU', 'ortu_app'),     env('DB_PASSWORD_ORTU', 'ortu_app')],
        ];
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $a = $this->accounts();

        foreach ($a as $role => [$user, $pass]) {
            DB::unprepared("CREATE USER IF NOT EXISTS '{$user}'@'localhost' IDENTIFIED BY '{$pass}'");
        }

        $t = fn (string $name) => "`{$db}`.`{$name}`";
        $func = fn (string $name) => "FUNCTION `{$db}`.`{$name}`";

        $grant = function (string $user, array $map) {
            foreach ($map as $object => $privileges) {
                DB::unprepared("GRANT {$privileges} ON {$object} TO '{$user}'@'localhost'");
            }
        };


        $grant($a['admin'][0], [
            $t('jabatan') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('pengurus_kelas') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('pengurus_organisasi') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('kelas') => 'SELECT',
            $t('anggota_kelas') => 'SELECT',
            $t('organisasi') => 'SELECT',
            $t('tahun_ajaran') => 'SELECT',
            $t('guru') => 'SELECT',
            $t('siswa') => 'SELECT',
            $t('v_pengurus_lengkap') => 'SELECT',
            $t('v_pengurus_organisasi_lengkap') => 'SELECT',
            $t('v_ringkasan_perangkat') => 'SELECT',
            $func('fn_jumlah_pengurus_kelas') => 'EXECUTE',
            $func('fn_jumlah_pengurus_organisasi') => 'EXECUTE',
        ]);




        $grant($a['guru'][0], [
            $t('jabatan') => 'SELECT',
            $t('pengurus_kelas') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('pengurus_organisasi') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('kelas') => 'SELECT',
            $t('anggota_kelas') => 'SELECT',
            $t('organisasi') => 'SELECT',
            $t('tahun_ajaran') => 'SELECT',
            $t('guru') => 'SELECT',
            $t('siswa') => 'SELECT',
            $t('v_pengurus_lengkap') => 'SELECT',
            $t('v_pengurus_organisasi_lengkap') => 'SELECT',
            $t('v_ringkasan_perangkat') => 'SELECT',
            $func('fn_jumlah_pengurus_kelas') => 'EXECUTE',
            $func('fn_jumlah_pengurus_organisasi') => 'EXECUTE',
        ]);


        $grant($a['siswa'][0], [
            $t('jabatan') => 'SELECT',
            $t('pengurus_kelas') => 'SELECT',
            $t('pengurus_organisasi') => 'SELECT',
            $t('kelas') => 'SELECT',
            $t('anggota_kelas') => 'SELECT',
            $t('organisasi') => 'SELECT',
            $t('tahun_ajaran') => 'SELECT',
            $t('guru') => 'SELECT',
            $t('siswa') => 'SELECT',
            $t('v_pengurus_lengkap') => 'SELECT',
            $t('v_pengurus_organisasi_lengkap') => 'SELECT',
            $t('v_ringkasan_perangkat') => 'SELECT',
            $func('fn_jumlah_pengurus_kelas') => 'EXECUTE',
            $func('fn_jumlah_pengurus_organisasi') => 'EXECUTE',
        ]);




        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');

        $objek = [
            'jabatan', 'pengurus_kelas', 'pengurus_organisasi',
            'v_pengurus_lengkap', 'v_pengurus_organisasi_lengkap', 'v_ringkasan_perangkat',
        ];

        $fungsi = ['fn_jumlah_pengurus_kelas', 'fn_jumlah_pengurus_organisasi'];

        foreach ($this->accounts() as $role => [$user]) {
            foreach ($objek as $o) {
                DB::unprepared("REVOKE ALL PRIVILEGES ON `{$db}`.`{$o}` FROM '{$user}'@'localhost'");
            }
            foreach ($fungsi as $f) {
                DB::unprepared("REVOKE ALL PRIVILEGES ON FUNCTION `{$db}`.`{$f}` FROM '{$user}'@'localhost'");
            }
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }
};
