<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OtorisasiTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVotingDatabase();
    }

    public function test_siswa_tidak_bisa_masuk_halaman_kelola(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('kelola.pemilihan.index'))
            ->assertForbidden();
    }

    public function test_guru_bukan_panitia_tidak_bisa_mengelola(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_guru_lain']))
            ->get(route('kelola.pemilihan.monitor', $d['id_pemilihan']))
            ->assertForbidden();
    }

    public function test_panitia_bisa_membuka_monitor(): void
    {
        $d = $this->seedPemilihanDraf();
        DB::table('panitia_pemilihan')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'id_user' => $d['id_panitia_user'],
        ]);

        $this->actingAs($this->userDari($d['id_panitia_user']))
            ->get(route('kelola.pemilihan.monitor', $d['id_pemilihan']))
            ->assertOk();
    }

    public function test_admin_bisa_membuka_daftar_kelola(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('kelola.pemilihan.index'))
            ->assertOk();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->get(route('kelola.pemilihan.index'))->assertRedirect(route('login'));
        $this->get(route('siswa.pemilihan.index'))->assertRedirect(route('login'));
    }
}
