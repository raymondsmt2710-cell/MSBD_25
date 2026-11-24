<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function namaView(): string
    {
        return 'v_portal_anak_orang_tua';
    }

    private function namaUserOrtu(): string
    {
        return env('DB_USERNAME_ORTU', 'ortu_app');
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

        $view = $this->namaView();

        DB::unprepared("DROP VIEW IF EXISTS `{$view}`");




        DB::unprepared(<<<SQL
CREATE VIEW `{$view}` AS
SELECT
    ot.id_orang_tua,
    ots.hubungan,
    ots.is_kontak_utama,
    s.id_siswa,
    s.nis,
    s.nisn,
    s.nama_siswa,
    s.jenis_kelamin,
    s.status AS status_siswa,
    s.foto_url,
    k.id_kelas,
    k.nama_kelas,
    k.tingkat,
    k.jurusan,
    ta.nama AS nama_tahun_ajaran
FROM orang_tua ot
INNER JOIN orang_tua_siswa ots ON ots.id_orang_tua = ot.id_orang_tua
INNER JOIN siswa s ON s.id_siswa = ots.id_siswa
LEFT JOIN anggota_kelas ak ON ak.id_anggota = (
        SELECT MAX(a2.id_anggota)
        FROM anggota_kelas a2
        INNER JOIN kelas k2 ON k2.id_kelas = a2.id_kelas
        INNER JOIN tahun_ajaran t2 ON t2.id_tahun_ajaran = k2.id_tahun_ajaran
        WHERE a2.id_siswa = s.id_siswa AND t2.is_aktif = 1
    )
LEFT JOIN kelas k ON k.id_kelas = ak.id_kelas
LEFT JOIN tahun_ajaran ta ON ta.id_tahun_ajaran = k.id_tahun_ajaran
SQL);




        $db = config('database.connections.mysql.database');
        $ortu = $this->namaUserOrtu();
        $host = $this->userHost();

        DB::unprepared("GRANT SELECT ON `{$db}`.`{$view}` TO '{$ortu}'@'{$host}'");
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $view = $this->namaView();
        $db = config('database.connections.mysql.database');
        $ortu = $this->namaUserOrtu();
        $host = $this->userHost();

        try {
            DB::unprepared("REVOKE SELECT ON `{$db}`.`{$view}` FROM '{$ortu}'@'{$host}'");
        } catch (\Throwable $e) {

        }

        DB::unprepared("DROP VIEW IF EXISTS `{$view}`");
    }
};
