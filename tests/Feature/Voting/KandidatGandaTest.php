<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KandidatGandaTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVotingDatabase();
    }

    public function test_ketua_yang_sama_tidak_bisa_didaftarkan_dua_kali(): void
    {
        $d = $this->seedPemilihanDraf();


        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
                'nomor_urut' => 3,
                'id_siswa_ketua' => $d['id_siswa'][0],
            ])
            ->assertSessionHasErrors('id_siswa_ketua');

        $this->assertSame(2, DB::connection('mysql')->table('kandidat')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_siswa_yang_sudah_jadi_ketua_tidak_bisa_jadi_wakil(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
                'nomor_urut' => 3,
                'id_siswa_ketua' => $d['id_siswa'][1],
                'id_siswa_wakil' => $d['id_siswa'][0],
            ])
            ->assertSessionHasErrors('id_siswa_wakil');

        $this->assertSame(2, DB::connection('mysql')->table('kandidat')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_siswa_yang_sudah_jadi_wakil_tidak_bisa_jadi_ketua(): void
    {
        $d = $this->seedPemilihanDraf();


        $idWakil = DB::connection('mysql')->table('siswa')->insertGetId([
            'id_user' => DB::connection('mysql')->table('users')->insertGetId([
                'username' => 'uji_wakil_baru',
                'password_hash' => bcrypt('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]),
            'nis' => '9500001', 'nisn' => '9500000001',
            'nama_siswa' => 'Siswa Wakil Baru', 'jenis_kelamin' => 'P', 'status' => 'aktif',
        ]);

        DB::connection('mysql')->table('kandidat')
            ->where('id_pemilihan', $d['id_pemilihan'])->where('nomor_urut', 1)
            ->update(['id_siswa_wakil' => $idWakil]);


        $idKetiga = DB::connection('mysql')->table('siswa')->insertGetId([
            'id_user' => DB::connection('mysql')->table('users')->insertGetId([
                'username' => 'uji_ketiga_wakil',
                'password_hash' => bcrypt('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]),
            'nis' => '9500002', 'nisn' => '9500000002',
            'nama_siswa' => 'Siswa Ketiga Wakil', 'jenis_kelamin' => 'L', 'status' => 'aktif',
        ]);

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
                'nomor_urut' => 3,
                'id_siswa_ketua' => $idKetiga,
                'id_siswa_wakil' => $idWakil,
            ])
            ->assertSessionHasErrors('id_siswa_wakil');
    }

    public function test_ketua_dan_wakil_dalam_satu_kandidat_tidak_boleh_orang_yang_sama(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
                'nomor_urut' => 3,
                'id_siswa_ketua' => $d['id_siswa'][0],
                'id_siswa_wakil' => $d['id_siswa'][0],
            ])
            ->assertSessionHasErrors('id_siswa_wakil');
    }

    public function test_dua_siswa_berbeda_tetap_bisa_mendaftar(): void
    {
        $d = $this->seedPemilihanDraf();


        $idKetiga = DB::connection('mysql')->table('siswa')->insertGetId([
            'id_user' => DB::connection('mysql')->table('users')->insertGetId([
                'username' => 'uji_tambahan_ketiga',
                'password_hash' => bcrypt('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]),
            'nis' => '9000001', 'nisn' => '9000000001',
            'nama_siswa' => 'Siswa Ketiga', 'jenis_kelamin' => 'L', 'status' => 'aktif',
        ]);

        $idKeempat = DB::connection('mysql')->table('siswa')->insertGetId([
            'id_user' => DB::connection('mysql')->table('users')->insertGetId([
                'username' => 'uji_tambahan_keempat',
                'password_hash' => bcrypt('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]),
            'nis' => '9000002', 'nisn' => '9000000002',
            'nama_siswa' => 'Siswa Keempat', 'jenis_kelamin' => 'P', 'status' => 'aktif',
        ]);

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
                'nomor_urut' => 3,
                'id_siswa_ketua' => $idKetiga,
                'id_siswa_wakil' => $idKeempat,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(3, DB::connection('mysql')->table('kandidat')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_edit_kandidat_tidak_menabrak_diri_sendiri(): void
    {
        $d = $this->seedPemilihanDraf();


        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
                'id_kandidat' => $d['id_kandidat'][0],
                'nomor_urut' => 1,
                'id_siswa_ketua' => $d['id_siswa'][0],
                'visi' => 'Visi diperbarui',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Visi diperbarui', DB::connection('mysql')->table('kandidat')
            ->where('id_kandidat', $d['id_kandidat'][0])->value('visi'));
    }
}
