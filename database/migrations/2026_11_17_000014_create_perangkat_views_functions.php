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
        'v_pengurus_lengkap',
        'v_pengurus_organisasi_lengkap',
        'v_ringkasan_perangkat',
    ];

    private array $functions = [
        'fn_jumlah_pengurus_kelas',
        'fn_jumlah_pengurus_organisasi',
    ];

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }




        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_pengurus_lengkap AS
SELECT
    pk.id_pengurus_kelas,
    pk.id_kelas,
    k.nama_kelas,
    k.tingkat,
    k.jurusan,
    pk.id_siswa,
    s.nis,
    s.nama_siswa,
    s.jenis_kelamin,
    s.foto_url,
    pk.id_jabatan,
    j.nama_jabatan,
    j.urutan,
    j.lingkup
FROM pengurus_kelas pk
INNER JOIN kelas k ON k.id_kelas = pk.id_kelas
INNER JOIN siswa s ON s.id_siswa = pk.id_siswa
INNER JOIN jabatan j ON j.id_jabatan = pk.id_jabatan
SQL);


        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_pengurus_organisasi_lengkap AS
SELECT
    po.id_pengurus_org,
    po.id_organisasi,
    o.nama_organisasi,
    o.singkatan,
    po.id_tahun_ajaran,
    t.nama AS nama_tahun_ajaran,
    po.id_siswa,
    s.nis,
    s.nama_siswa,
    s.jenis_kelamin,
    s.foto_url,
    po.id_jabatan,
    j.nama_jabatan,
    j.urutan,
    po.bidang
FROM pengurus_organisasi po
INNER JOIN organisasi o ON o.id_organisasi = po.id_organisasi
INNER JOIN tahun_ajaran t ON t.id_tahun_ajaran = po.id_tahun_ajaran
INNER JOIN siswa s ON s.id_siswa = po.id_siswa
INNER JOIN jabatan j ON j.id_jabatan = po.id_jabatan
SQL);



        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_ringkasan_perangkat AS
SELECT
    'kelas' AS jenis,
    k.id_kelas AS id,
    k.nama_kelas AS nama,
    k.id_tahun_ajaran,
    (SELECT COUNT(*) FROM pengurus_kelas pk WHERE pk.id_kelas = k.id_kelas) AS jumlah_pengurus,
    (SELECT COUNT(*) FROM anggota_kelas ak WHERE ak.id_kelas = k.id_kelas) AS jumlah_anggota
FROM kelas k
UNION ALL
SELECT
    'organisasi' AS jenis,
    o.id_organisasi AS id,
    o.nama_organisasi AS nama,
    NULL AS id_tahun_ajaran,
    (SELECT COUNT(*) FROM pengurus_organisasi po WHERE po.id_organisasi = o.id_organisasi) AS jumlah_pengurus,
    NULL AS jumlah_anggota
FROM organisasi o
SQL);



        foreach ($this->functions as $f) {
            DB::unprepared("DROP FUNCTION IF EXISTS {$f}");
        }

        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_jumlah_pengurus_kelas(p_id_kelas INT)
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT COUNT(*) INTO v_jumlah
    FROM pengurus_kelas
    WHERE id_kelas = p_id_kelas;

    RETURN v_jumlah;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_jumlah_pengurus_organisasi(p_id_organisasi INT, p_id_tahun_ajaran INT)
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT COUNT(*) INTO v_jumlah
    FROM pengurus_organisasi
    WHERE id_organisasi = p_id_organisasi
      AND id_tahun_ajaran = p_id_tahun_ajaran;

    RETURN v_jumlah;
END
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

        foreach ($this->functions as $f) {
            DB::unprepared("DROP FUNCTION IF EXISTS {$f}");
        }
    }
};
