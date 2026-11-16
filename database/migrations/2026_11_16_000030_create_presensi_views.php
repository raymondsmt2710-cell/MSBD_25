<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Database View (materi View & Query Kompleks): JOIN panjang disimpan sekali, dibaca dengan DB::table('nama_view').
return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_rekap_presensi_siswa AS
SELECT
    p.id_presensi,
    p.id_sesi,
    p.id_siswa,
    s.nis,
    s.nama_siswa,
    k.id_kelas,
    k.nama_kelas,
    m.nama_mapel,
    sp.tanggal,
    jp.hari,
    jp.jam_mulai,
    jp.jam_selesai,
    p.status,
    p.metode,
    p.waktu_presensi,
    p.catatan
FROM presensi p
INNER JOIN siswa s ON s.id_siswa = p.id_siswa
INNER JOIN sesi_presensi sp ON sp.id_sesi = p.id_sesi
INNER JOIN jadwal_pelajaran jp ON jp.id_jadwal = sp.id_jadwal
INNER JOIN kelas k ON k.id_kelas = jp.id_kelas
INNER JOIN mata_pelajaran m ON m.id_mapel = jp.id_mapel
SQL);

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_rekap_sesi_presensi AS
SELECT
    sp.id_sesi,
    sp.tanggal,
    sp.status AS status_sesi,
    jp.id_guru,
    jp.id_kelas,
    k.nama_kelas,
    m.nama_mapel,
    g.nama_guru,
    COUNT(p.id_presensi) AS total_tercatat,
    COALESCE(SUM(p.status = 'hadir'), 0) AS hadir,
    COALESCE(SUM(p.status = 'izin'), 0) AS izin,
    COALESCE(SUM(p.status = 'sakit'), 0) AS sakit,
    COALESCE(SUM(p.status = 'alpa'), 0) AS alpa
FROM sesi_presensi sp
INNER JOIN jadwal_pelajaran jp ON jp.id_jadwal = sp.id_jadwal
INNER JOIN kelas k ON k.id_kelas = jp.id_kelas
INNER JOIN mata_pelajaran m ON m.id_mapel = jp.id_mapel
INNER JOIN guru g ON g.id_guru = jp.id_guru
LEFT JOIN presensi p ON p.id_sesi = sp.id_sesi
GROUP BY sp.id_sesi, sp.tanggal, sp.status, jp.id_guru, jp.id_kelas,
         k.nama_kelas, m.nama_mapel, g.nama_guru
SQL);
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        DB::unprepared('DROP VIEW IF EXISTS v_rekap_presensi_siswa');
        DB::unprepared('DROP VIEW IF EXISTS v_rekap_sesi_presensi');
    }
};
