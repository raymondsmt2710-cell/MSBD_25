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

    /**
     * Host akun MySQL per peran. Harus mengikuti DB_USER_HOST supaya GRANT
     * berlaku saat database berada di container/host terpisah (mis. Railway).
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

        $db = config('database.connections.mysql.database');
        $a = $this->accounts();
        $host = $this->userHost();

        foreach ($a as [$user, $pass]) {
            DB::unprepared("CREATE USER IF NOT EXISTS '{$user}'@'{$host}' IDENTIFIED BY '{$pass}'");
        }

        $t = fn (string $name) => "`{$db}`.`{$name}`";
        $proc = fn (string $name) => "PROCEDURE `{$db}`.`{$name}`";
        $func = fn (string $name) => "FUNCTION `{$db}`.`{$name}`";

        $grant = function (string $user, array $map) use ($host) {
            foreach ($map as $object => $privileges) {
                DB::unprepared("GRANT {$privileges} ON {$object} TO '{$user}'@'{$host}'");
            }
        };

        $kelolaProcedure = [
            'sp_simpan_pemilihan', 'sp_simpan_kandidat', 'sp_hapus_kandidat',
            'sp_daftarkan_pemilih_kelas', 'sp_hapus_pemilih', 'sp_tunjuk_panitia',
            'sp_buka_pemilihan', 'sp_tutup_pemilihan', 'sp_kembalikan_ke_draf',
        ];

        $fungsiUmum = ['fn_sudah_memilih', 'fn_boleh_memilih', 'fn_partisipasi'];


        $map = [
            $t('pemilihan') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('kandidat') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('daftar_pemilih') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('panitia_pemilihan') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('suara') => 'SELECT',
            $t('log_pemilihan') => 'SELECT',
            $t('v_kandidat_pemilihan') => 'SELECT',
            $t('v_kandidat_pemilihan_hasil') => 'SELECT',
            $t('v_hasil_pemilihan') => 'SELECT',
            $t('v_partisipasi_pemilihan') => 'SELECT',
            $t('v_pemilihan_pengelola') => 'SELECT',
            $proc('sp_rekam_suara') => 'EXECUTE',
            $func('fn_total_suara') => 'EXECUTE',
        ];
        foreach ($kelolaProcedure as $p) {
            $map[$proc($p)] = 'EXECUTE';
        }
        foreach ($fungsiUmum as $f) {
            $map[$func($f)] = 'EXECUTE';
        }
        $grant($a['admin'][0], $map);





        $map = [
            $t('pemilihan') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('kandidat') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('daftar_pemilih') => 'SELECT, INSERT, UPDATE, DELETE',
            $t('panitia_pemilihan') => 'SELECT',
            $t('suara') => 'SELECT',
            $t('log_pemilihan') => 'SELECT',
            $t('siswa') => 'SELECT',
            $t('kelas') => 'SELECT',
            $t('anggota_kelas') => 'SELECT',
            $t('organisasi') => 'SELECT',
            $t('tahun_ajaran') => 'SELECT',
            $t('users') => 'SELECT',
            $t('guru') => 'SELECT',
            $t('v_kandidat_pemilihan') => 'SELECT',
            $t('v_kandidat_pemilihan_hasil') => 'SELECT',
            $t('v_hasil_pemilihan') => 'SELECT',
            $t('v_partisipasi_pemilihan') => 'SELECT',
            $t('v_pemilihan_pengelola') => 'SELECT',
            $proc('sp_rekam_suara') => 'EXECUTE',
            $func('fn_total_suara') => 'EXECUTE',
        ];
        foreach ($kelolaProcedure as $p) {
            $map[$proc($p)] = 'EXECUTE';
        }
        foreach ($fungsiUmum as $f) {
            $map[$func($f)] = 'EXECUTE';
        }
        $grant($a['guru'][0], $map);




        $map = [
            $t('pemilihan') => 'SELECT',
            $t('kandidat') => 'SELECT',
            $t('daftar_pemilih') => 'SELECT',
            $t('users') => 'SELECT',
            $t('siswa') => 'SELECT',
            $t('organisasi') => 'SELECT',
            $t('tahun_ajaran') => 'SELECT',
            $t('v_kandidat_pemilihan') => 'SELECT',
            $t('v_hasil_pemilihan') => 'SELECT',
            $t('v_partisipasi_pemilihan') => 'SELECT',
            $proc('sp_rekam_suara') => 'EXECUTE',
        ];
        foreach ($fungsiUmum as $f) {
            $map[$func($f)] = 'EXECUTE';
        }
        $grant($a['siswa'][0], $map);



        $map = [
            $t('pemilihan') => 'SELECT',
            $t('kandidat') => 'SELECT',
            $t('users') => 'SELECT',
            $t('siswa') => 'SELECT',
            $t('v_kandidat_pemilihan') => 'SELECT',
            $t('v_hasil_pemilihan') => 'SELECT',
            $t('v_partisipasi_pemilihan') => 'SELECT',
        ];
        $grant($a['orangtua'][0], $map);

        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = $this->userHost();

        $objek = [
            'pemilihan', 'kandidat', 'daftar_pemilih', 'panitia_pemilihan',
            'suara', 'log_pemilihan',
            'v_kandidat_pemilihan', 'v_kandidat_pemilihan_hasil',
            'v_hasil_pemilihan', 'v_partisipasi_pemilihan', 'v_pemilihan_pengelola',
        ];

        $prosedur = [
            'sp_rekam_suara', 'sp_simpan_pemilihan', 'sp_simpan_kandidat',
            'sp_hapus_kandidat', 'sp_daftarkan_pemilih_kelas', 'sp_hapus_pemilih',
            'sp_tunjuk_panitia', 'sp_buka_pemilihan', 'sp_tutup_pemilihan',
            'sp_kembalikan_ke_draf',
        ];

        $fungsi = ['fn_sudah_memilih', 'fn_boleh_memilih', 'fn_total_suara', 'fn_partisipasi'];

        foreach ($this->accounts() as [$user]) {
            foreach ($objek as $o) {
                DB::unprepared("REVOKE ALL PRIVILEGES ON `{$db}`.`{$o}` FROM '{$user}'@'{$host}'");
            }
            foreach ($prosedur as $p) {
                DB::unprepared("REVOKE ALL PRIVILEGES ON PROCEDURE `{$db}`.`{$p}` FROM '{$user}'@'{$host}'");
            }
            foreach ($fungsi as $f) {
                DB::unprepared("REVOKE ALL PRIVILEGES ON FUNCTION `{$db}`.`{$f}` FROM '{$user}'@'{$host}'");
            }
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }
};
