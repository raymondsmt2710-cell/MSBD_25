<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// View (materi View & Query Kompleks) + DCL (materi Manajemen User & Hak Akses).
//
// Fitur 7: portal pemantauan orang tua/wali.
//
// Orang tua cukup diberi SELECT pada SATU view ini, bukan pada tabel kelas /
// anggota_kelas / tahun_ajaran. View berjalan dengan SQL SECURITY DEFINER (default
// MySQL), sehingga ortu_app tidak perlu GRANT pada tabel dasar yang di-JOIN di dalamnya.
//
// Nomor 000014 dipakai karena 000001-000013 sudah dipakai migrasi fitur pemilihan.
// Versi asli migrasi ini memakai nomor 000001 dan bertabrakan dengan
// 2026_11_17_000001_create_log_pemilihan_table.
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

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $view = $this->namaView();

        DB::unprepared("DROP VIEW IF EXISTS `{$view}`");

        // Satu baris per (orang_tua, siswa). Kelas diambil lewat subquery MAX(id_anggota)
        // pada tahun ajaran aktif, sehingga siswa dengan riwayat beberapa kelas
        // (atau beberapa baris anggota_kelas) tidak muncul ganda.
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

        // GRANT bersifat idempoten di MySQL (mengulang GRANT yang sama tidak error),
        // jadi aman dijalankan berkali-kali. FLUSH PRIVILEGES tidak diperlukan karena
        // GRANT langsung berlaku; memanggilnya hanya menambah beban.
        $db = config('database.connections.mysql.database');
        $ortu = $this->namaUserOrtu();

        DB::unprepared("GRANT SELECT ON `{$db}`.`{$view}` TO '{$ortu}'@'localhost'");
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $view = $this->namaView();
        $db = config('database.connections.mysql.database');
        $ortu = $this->namaUserOrtu();

        // REVOKE bisa gagal bila grant sudah tidak ada (mis. user MySQL-nya dihapus),
        // dan kegagalan itu tidak boleh menggagalkan rollback migrasi.
        try {
            DB::unprepared("REVOKE SELECT ON `{$db}`.`{$view}` FROM '{$ortu}'@'localhost'");
        } catch (\Throwable $e) {
            // diabaikan: grant memang sudah tidak ada
        }

        DB::unprepared("DROP VIEW IF EXISTS `{$view}`");
    }
};
