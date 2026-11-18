<?php

namespace Tests\Feature\Organisasi;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Objek database untuk fitur perangkat organisasi & kelas:
 * view, function, trigger, dan index.
 */
class ObjekDatabaseTest extends TestCase
{
    use DatabaseTruncation;
    use OrganisasiTestSupport;

    public function test_view_pengurus_lengkap_ada(): void
    {
        $d = $this->seedPerangkat();

        $baris = DB::connection('mysql')->table('v_pengurus_lengkap')
            ->where('id_kelas', $d['id_kelas'])
            ->get();

        $this->assertNotEmpty($baris);

        // View harus menyertakan nama siswa, nama jabatan, dan urutan jabatan.
        $kolom = array_keys((array) $baris->first());
        $this->assertContains('nama_siswa', $kolom);
        $this->assertContains('nama_jabatan', $kolom);
        $this->assertContains('urutan', $kolom);
    }

    public function test_view_pengurus_organisasi_lengkap_ada(): void
    {
        $d = $this->seedPerangkat();

        $baris = DB::connection('mysql')->table('v_pengurus_organisasi_lengkap')
            ->where('id_organisasi', $d['id_organisasi'])
            ->get();

        $this->assertNotEmpty($baris);
        $kolom = array_keys((array) $baris->first());
        $this->assertContains('nama_siswa', $kolom);
        $this->assertContains('nama_jabatan', $kolom);
        $this->assertContains('nama_tahun_ajaran', $kolom);
    }

    public function test_view_ringkasan_perangkat_menghitung_pengurus(): void
    {
        $d = $this->seedPerangkat();

        $kelas = DB::connection('mysql')->table('v_ringkasan_perangkat')
            ->where('jenis', 'kelas')->where('id', $d['id_kelas'])->first();

        $this->assertNotNull($kelas);
        $this->assertGreaterThan(0, (int) $kelas->jumlah_pengurus);
    }

    public function test_function_jumlah_pengurus_kelas(): void
    {
        $d = $this->seedPerangkat();

        $hasil = DB::connection('mysql')->selectOne(
            'SELECT fn_jumlah_pengurus_kelas(?) AS n', [$d['id_kelas']]
        );

        $this->assertGreaterThan(0, (int) $hasil->n);
    }

    public function test_function_jumlah_pengurus_organisasi(): void
    {
        $d = $this->seedPerangkat();

        $hasil = DB::connection('mysql')->selectOne(
            'SELECT fn_jumlah_pengurus_organisasi(?, ?) AS n',
            [$d['id_organisasi'], $d['id_tahun_ajaran']]
        );

        $this->assertGreaterThan(0, (int) $hasil->n);
    }

    public function test_trigger_menolak_siswa_bukan_anggota_kelas(): void
    {
        $d = $this->seedPerangkat();

        // Siswa dari kelas lain (tidak terdaftar di anggota_kelas kelas ini).
        $idSiswaLain = DB::connection('mysql')->table('siswa')->insertGetId([
            'id_user' => DB::connection('mysql')->table('users')->insertGetId([
                'username' => 'org_siswa_luar',
                'password_hash' => bcrypt('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]),
            'nis' => '8800001', 'nisn' => '8800000001',
            'nama_siswa' => 'Siswa Luar', 'jenis_kelamin' => 'L', 'status' => 'aktif',
        ]);

        $this->expectException(\Throwable::class);

        DB::connection('mysql')->table('pengurus_kelas')->insert([
            'id_kelas' => $d['id_kelas'],
            'id_siswa' => $idSiswaLain,
            'id_jabatan' => $d['id_jabatan_kelas'][0],
        ]);
    }

    public function test_trigger_menolak_jabatan_lingkup_salah(): void
    {
        $d = $this->seedPerangkat();

        $this->expectException(\Throwable::class);

        // Jabatan lingkup 'organisasi' dipakai di pengurus_kelas.
        DB::connection('mysql')->table('pengurus_kelas')->insert([
            'id_kelas' => $d['id_kelas'],
            'id_siswa' => $d['id_siswa'][0],
            'id_jabatan' => $d['id_jabatan_organisasi'][0],
        ]);
    }

    public function test_index_tersedia(): void
    {
        $idxKelas = collect(DB::select('SHOW INDEX FROM pengurus_kelas'))->pluck('Key_name');
        $this->assertContains('idx_pengurus_kelas', $idxKelas);

        $idxOrg = collect(DB::select('SHOW INDEX FROM pengurus_organisasi'))->pluck('Key_name');
        $this->assertContains('idx_pengurus_organisasi', $idxOrg);
    }
}
