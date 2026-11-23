<?php

namespace Tests\Feature\Organisasi;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KelolaPerangkatTest extends TestCase
{
    use DatabaseTruncation;
    use OrganisasiTestSupport;

    public function test_wali_kelas_bisa_membuka_kelola(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_wali_user']))
            ->get(route('perangkat.kelola.index'))
            ->assertOk()
            ->assertSee($d['id_kelas'] ? 'X Uji' : 'x');
    }

    public function test_wali_kelas_bisa_membuka_form_kelasnya(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_wali_user']))
            ->get(route('perangkat.kelola.kelas', $d['id_kelas']))
            ->assertOk()
            ->assertSee('Susunan Pengurus');
    }

    public function test_guru_lain_tidak_bisa_membuka_form_kelas_orang(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_guru_lain_user']))
            ->get(route('perangkat.kelola.kelas', $d['id_kelas']))
            ->assertForbidden();
    }

    public function test_guru_lain_tidak_bisa_menyimpan_kelas_orang(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_guru_lain_user']))
            ->post(route('perangkat.kelola.kelas.simpan', $d['id_kelas']), [
                'pengurus' => [$d['id_jabatan_kelas'][0] => $d['id_siswa'][2]],
            ])
            ->assertForbidden();


        $this->assertSame(2, DB::connection('mysql')->table('pengurus_kelas')
            ->where('id_kelas', $d['id_kelas'])->count());
    }

    public function test_admin_bisa_membuka_semua(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('perangkat.kelola.kelas', $d['id_kelas']))
            ->assertOk();

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('perangkat.kelola.organisasi', $d['id_organisasi']))
            ->assertOk();
    }

    public function test_siswa_tidak_bisa_membuka_kelola(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('perangkat.kelola.index'))
            ->assertForbidden();
    }

    public function test_orang_tua_tidak_bisa_membuka_kelola(): void
    {
        $d = $this->seedPerangkat();

        $idOrtu = DB::connection('mysql')->table('users')->insertGetId([
            'username' => 'ortu_kelola_uji',
            'password_hash' => bcrypt('password'),
            'role' => 'orang_tua',
            'is_active' => true,
        ]);

        $this->actingAs($this->userDari($idOrtu))
            ->get(route('perangkat.kelola.index'))
            ->assertForbidden();
    }

    public function test_wali_kelas_bisa_menyimpan_pengurus(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_wali_user']))
            ->post(route('perangkat.kelola.kelas.simpan', $d['id_kelas']), [
                'pengurus' => [
                    $d['id_jabatan_kelas'][0] => $d['id_siswa'][2],
                    $d['id_jabatan_kelas'][2] => $d['id_siswa'][3],
                ],
            ])
            ->assertRedirect(route('perangkat.kelola.kelas', $d['id_kelas']))
            ->assertSessionHas('success');


        $this->assertSame(2, DB::connection('mysql')->table('pengurus_kelas')
            ->where('id_kelas', $d['id_kelas'])->count());

        $this->assertDatabaseHas('pengurus_kelas', [
            'id_kelas' => $d['id_kelas'],
            'id_siswa' => $d['id_siswa'][2],
            'id_jabatan' => $d['id_jabatan_kelas'][0],
        ]);
    }

    public function test_menyimpan_form_kosong_mengosongkan_pengurus(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_wali_user']))
            ->post(route('perangkat.kelola.kelas.simpan', $d['id_kelas']), ['pengurus' => []])
            ->assertRedirect();

        $this->assertSame(0, DB::connection('mysql')->table('pengurus_kelas')
            ->where('id_kelas', $d['id_kelas'])->count());
    }

    public function test_siswa_ganda_dalam_satu_kirim_ditolak(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_wali_user']))
            ->post(route('perangkat.kelola.kelas.simpan', $d['id_kelas']), [
                'pengurus' => [
                    $d['id_jabatan_kelas'][0] => $d['id_siswa'][0],
                    $d['id_jabatan_kelas'][1] => $d['id_siswa'][0],
                ],
            ])
            ->assertSessionHas('error');
    }

    public function test_siswa_bukan_anggota_kelas_ditolak(): void
    {
        $d = $this->seedPerangkat();

        $idSiswaLuar = DB::connection('mysql')->table('siswa')->insertGetId([
            'id_user' => DB::connection('mysql')->table('users')->insertGetId([
                'username' => 'anggota_luar_uji',
                'password_hash' => bcrypt('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]),
            'nis' => '8600001', 'nisn' => '8600000001',
            'nama_siswa' => 'Siswa Luar Uji', 'jenis_kelamin' => 'L', 'status' => 'aktif',
        ]);


        $this->actingAs($this->userDari($d['id_wali_user']))
            ->post(route('perangkat.kelola.kelas.simpan', $d['id_kelas']), [
                'pengurus' => [$d['id_jabatan_kelas'][0] => $idSiswaLuar],
            ])
            ->assertSessionHas('error');
    }

    public function test_pembina_bisa_menyimpan_pengurus_organisasi(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_wali_user']))
            ->post(route('perangkat.kelola.organisasi.simpan', $d['id_organisasi']), [
                'id_tahun_ajaran' => $d['id_tahun_ajaran'],
                'pengurus' => [
                    $d['id_jabatan_organisasi'][0] => $d['id_siswa'][0],
                    $d['id_jabatan_organisasi'][3] => $d['id_siswa'][1],
                ],
                'bidang' => [
                    $d['id_jabatan_organisasi'][3] => 'Kaderisasi',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(2, DB::connection('mysql')->table('pengurus_organisasi')
            ->where('id_organisasi', $d['id_organisasi'])
            ->where('id_tahun_ajaran', $d['id_tahun_ajaran'])->count());

        $this->assertDatabaseHas('pengurus_organisasi', [
            'id_organisasi' => $d['id_organisasi'],
            'id_siswa' => $d['id_siswa'][1],
            'bidang' => 'Kaderisasi',
        ]);
    }

    public function test_menyimpan_organisasi_tidak_menghapus_tahun_lain(): void
    {
        $d = $this->seedPerangkat();

        $idTahunLama = DB::connection('mysql')->table('tahun_ajaran')->insertGetId([
            'nama' => '2024/2025', 'tanggal_mulai' => '2024-07-15',
            'tanggal_selesai' => '2025-06-28', 'is_aktif' => false,
        ]);

        DB::connection('mysql')->table('pengurus_organisasi')->insert([
            'id_organisasi' => $d['id_organisasi'],
            'id_tahun_ajaran' => $idTahunLama,
            'id_siswa' => $d['id_siswa'][3],
            'id_jabatan' => $d['id_jabatan_organisasi'][0],
            'bidang' => null,
        ]);

        $this->actingAs($this->userDari($d['id_wali_user']))
            ->post(route('perangkat.kelola.organisasi.simpan', $d['id_organisasi']), [
                'id_tahun_ajaran' => $d['id_tahun_ajaran'],
                'pengurus' => [$d['id_jabatan_organisasi'][0] => $d['id_siswa'][0]],
            ])
            ->assertRedirect();


        $this->assertSame(1, DB::connection('mysql')->table('pengurus_organisasi')
            ->where('id_organisasi', $d['id_organisasi'])
            ->where('id_tahun_ajaran', $idTahunLama)->count());
    }
}
