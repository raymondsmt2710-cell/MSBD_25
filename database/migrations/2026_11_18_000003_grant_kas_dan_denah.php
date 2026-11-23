<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{

    private const BACA = [
        'kelas', 'anggota_kelas', 'tahun_ajaran', 'siswa', 'guru', 'users',
        'jabatan', 'pengurus_kelas', 'jadwal_pelajaran', 'mata_pelajaran',
        'transaksi_kas', 'iuran_kas', 'denah_kelas', 'posisi_duduk', 'ruangan_sekolah',
    ];


    private const TULIS_SISWA = [
        'transaksi_kas' => 'INSERT, UPDATE',
        'iuran_kas' => 'INSERT, UPDATE',
        'denah_kelas' => 'INSERT, UPDATE',
        'posisi_duduk' => 'INSERT, UPDATE, DELETE',
    ];


    private const MILIK_FITUR = [
        'transaksi_kas', 'iuran_kas', 'denah_kelas', 'posisi_duduk', 'ruangan_sekolah',
        'jabatan', 'pengurus_kelas',
    ];

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
            Log::error("Migrasi 2026_11_18_000003 melewati: {$sql}. Kode {$kode}: ".$e->getMessage());
        }
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        if (filter_var(env('DB_SKIP_ROLE_USERS', false), FILTER_VALIDATE_BOOLEAN)) {
            Log::warning('Migrasi 2026_11_18_000003 dilewati karena DB_SKIP_ROLE_USERS=true.');

            return;
        }

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');

        $aman = [1044, 1133, 1142, 1146, 1227];

        foreach ($this->akun() as $user) {
            foreach (self::BACA as $tabel) {
                $this->jalankan("GRANT SELECT ON `{$db}`.`{$tabel}` TO '{$user}'@'{$host}'", $aman);
            }
        }

        $siswa = $this->akun()['siswa'];
        foreach (self::TULIS_SISWA as $tabel => $hak) {
            $this->jalankan("GRANT {$hak} ON `{$db}`.`{$tabel}` TO '{$siswa}'@'{$host}'", $aman);
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = env('DB_USER_HOST', 'localhost');



        foreach ($this->akun() as $user) {
            foreach (self::MILIK_FITUR as $tabel) {
                $this->jalankan("REVOKE ALL PRIVILEGES ON `{$db}`.`{$tabel}` FROM '{$user}'@'{$host}'", [1141, 1147, 1133, 1146]);
            }
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }
};
