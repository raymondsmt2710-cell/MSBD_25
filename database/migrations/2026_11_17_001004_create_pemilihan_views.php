<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private array $views = [
        'v_kandidat_pemilihan',
        'v_kandidat_pemilihan_hasil',
        'v_hasil_pemilihan',
        'v_partisipasi_pemilihan',
        'v_pemilihan_pengelola',
    ];

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }


        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_kandidat_pemilihan AS
SELECT
    k.id_kandidat,
    k.id_pemilihan,
    k.nomor_urut,
    k.id_siswa_ketua,
    sk.nama_siswa AS nama_ketua,
    sk.nis AS nis_ketua,
    k.id_siswa_wakil,
    sw.nama_siswa AS nama_wakil,
    k.visi,
    k.misi,
    k.foto_url,
    p.status AS status_pemilihan
FROM kandidat k
INNER JOIN pemilihan p ON p.id_pemilihan = k.id_pemilihan
INNER JOIN siswa sk ON sk.id_siswa = k.id_siswa_ketua
LEFT JOIN siswa sw ON sw.id_siswa = k.id_siswa_wakil
SQL);


        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_kandidat_pemilihan_hasil AS
SELECT
    k.id_kandidat,
    k.id_pemilihan,
    k.nomor_urut,
    sk.nama_siswa AS nama_ketua,
    sw.nama_siswa AS nama_wakil,
    k.foto_url,
    p.status AS status_pemilihan,
    (SELECT COUNT(*) FROM suara s WHERE s.id_kandidat = k.id_kandidat) AS jumlah_suara,
    (
        SELECT COUNT(*) FROM suara s2 WHERE s2.id_pemilihan = k.id_pemilihan
    ) AS total_suara_pemilihan
FROM kandidat k
INNER JOIN pemilihan p ON p.id_pemilihan = k.id_pemilihan
INNER JOIN siswa sk ON sk.id_siswa = k.id_siswa_ketua
LEFT JOIN siswa sw ON sw.id_siswa = k.id_siswa_wakil
SQL);


        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_hasil_pemilihan AS
SELECT
    k.id_pemilihan,
    k.id_kandidat,
    k.nomor_urut,
    sk.nama_siswa AS nama_ketua,
    sw.nama_siswa AS nama_wakil,
    k.foto_url,
    p.status AS status_pemilihan,
    CASE
        WHEN p.status = 'selesai'
        THEN (SELECT COUNT(*) FROM suara s WHERE s.id_kandidat = k.id_kandidat)
        ELSE NULL
    END AS jumlah_suara,
    CASE
        WHEN p.status = 'selesai'
        THEN COALESCE(
            ROUND(
                (SELECT COUNT(*) FROM suara s WHERE s.id_kandidat = k.id_kandidat) * 100
                / NULLIF((SELECT COUNT(*) FROM suara s3 WHERE s3.id_pemilihan = k.id_pemilihan), 0)
            , 2)
        , 0.00)
        ELSE NULL
    END AS persentase
FROM kandidat k
INNER JOIN pemilihan p ON p.id_pemilihan = k.id_pemilihan
INNER JOIN siswa sk ON sk.id_siswa = k.id_siswa_ketua
LEFT JOIN siswa sw ON sw.id_siswa = k.id_siswa_wakil
SQL);


        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_partisipasi_pemilihan AS
SELECT
    p.id_pemilihan,
    COUNT(dp.id_siswa) AS total_pemilih,
    COALESCE(SUM(dp.sudah_memilih = 1), 0) AS sudah_memilih,
    COALESCE(
        ROUND(SUM(dp.sudah_memilih = 1) * 100 / NULLIF(COUNT(dp.id_siswa), 0), 2),
        0.00
    ) AS persentase
FROM pemilihan p
LEFT JOIN daftar_pemilih dp ON dp.id_pemilihan = p.id_pemilihan
GROUP BY p.id_pemilihan
SQL);


        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_pemilihan_pengelola AS
SELECT
    p.id_pemilihan,
    p.judul,
    p.deskripsi,
    p.status,
    p.waktu_mulai,
    p.waktu_selesai,
    p.id_organisasi,
    o.nama_organisasi,
    o.singkatan AS singkatan_organisasi,
    p.id_tahun_ajaran,
    t.nama AS tahun_ajaran,
    p.dibuat_oleh,
    u.username AS dibuat_oleh_username,
    (SELECT COUNT(*) FROM kandidat k WHERE k.id_pemilihan = p.id_pemilihan) AS jumlah_kandidat,
    (SELECT COUNT(*) FROM daftar_pemilih dp WHERE dp.id_pemilihan = p.id_pemilihan) AS jumlah_pemilih,
    (SELECT COUNT(*) FROM suara s WHERE s.id_pemilihan = p.id_pemilihan) AS jumlah_suara
FROM pemilihan p
INNER JOIN organisasi o ON o.id_organisasi = p.id_organisasi
INNER JOIN tahun_ajaran t ON t.id_tahun_ajaran = p.id_tahun_ajaran
INNER JOIN users u ON u.id_user = p.dibuat_oleh
SQL);
    }

    public function down(): void
    {
        if (! $this->mysql()) {
            return;
        }

        foreach ($this->views as $v) {
            DB::unprepared("DROP VIEW IF EXISTS {$v}");
        }
    }
};
