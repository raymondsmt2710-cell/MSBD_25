<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Fitur 8 (dashboard siswa): view perangkat kelas & organisasi.
//
// siswa_app sengaja TIDAK diberi hak baca pada tabel dasar pengurus_kelas,
// pengurus_organisasi, jabatan, maupun kelas (prinsip least privilege, lihat
// migrasi 000033). Halaman /siswa/perangkat karena itu membaca view sempit ini,
// bukan tabelnya langsung.
//
// View memakai SQL SECURITY DEFINER (bawaan MySQL), sehingga siswa_app cukup
// diberi SELECT pada view-nya saja; hak baca tabel dasar tetap dipegang pemilik
// view dan tidak menular ke siswa_app.
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

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_perangkat_kelas_siswa AS
SELECT
    pk.id_siswa,
    k.nama_kelas,
    j.nama_jabatan,
    j.urutan,
    s.nama_siswa,
    s.nis
FROM pengurus_kelas pk
INNER JOIN kelas k ON k.id_kelas = pk.id_kelas
INNER JOIN jabatan j ON j.id_jabatan = pk.id_jabatan
INNER JOIN siswa s ON s.id_siswa = pk.id_siswa
SQL);

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_perangkat_organisasi_siswa AS
SELECT
    po.id_siswa,
    o.nama_organisasi,
    j.nama_jabatan,
    j.urutan,
    po.bidang,
    ta.nama AS nama_tahun_ajaran,
    s.nama_siswa,
    s.nis
FROM pengurus_organisasi po
INNER JOIN organisasi o ON o.id_organisasi = po.id_organisasi
INNER JOIN jabatan j ON j.id_jabatan = po.id_jabatan
INNER JOIN tahun_ajaran ta ON ta.id_tahun_ajaran = po.id_tahun_ajaran
INNER JOIN siswa s ON s.id_siswa = po.id_siswa
SQL);

        $this->beriHak();
    }

    private function beriHak(): void
    {
        if (filter_var(env('DB_SKIP_ROLE_USERS', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = $this->userHost();
        $siswa = env('DB_USERNAME_SISWA', 'siswa_app');

        try {
            foreach (['v_perangkat_kelas_siswa', 'v_perangkat_organisasi_siswa'] as $view) {
                DB::unprepared("GRANT SELECT ON `{$db}`.`{$view}` TO '{$siswa}'@'{$host}'");
            }

            DB::unprepared('FLUSH PRIVILEGES');
        } catch (\Throwable $e) {
            // Dinaikkan ke error, bukan warning: tanpa GRANT ini halaman
            // /siswa/perangkat pasti gagal dengan error 1142 di produksi.
            Log::error('Migrasi 000016: gagal memberi hak SELECT view perangkat. Pesan: '.$e->getMessage());

            throw $e;
        }
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = $this->userHost();
        $siswa = env('DB_USERNAME_SISWA', 'siswa_app');

        foreach (['v_perangkat_kelas_siswa', 'v_perangkat_organisasi_siswa'] as $view) {
            try {
                DB::unprepared("REVOKE SELECT ON `{$db}`.`{$view}` FROM '{$siswa}'@'{$host}'");
            } catch (\Throwable $e) {
                // REVOKE bisa gagal bila GRANT sudah tidak ada; bukan kegagalan.
            }

            DB::unprepared("DROP VIEW IF EXISTS {$view}");
        }
    }
};
