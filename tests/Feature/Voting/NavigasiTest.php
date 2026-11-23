<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class NavigasiTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVotingDatabase();
    }

    public function test_tamu_melihat_landing_page_dari_beranda(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('SakuSaka')
            ->assertSee(route('login'), false);
    }

    public function test_siswa_diarahkan_ke_halaman_pemilihan_dari_beranda(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get('/')
            ->assertRedirect(route('siswa.pemilihan.index'));
    }

    public function test_admin_diarahkan_ke_panel_admin_dari_beranda(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_route_dashboard_tidak_dipakai_lagi(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('dashboard'));
    }

    public function test_beranda_siswa_punya_menu_pemilihan(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))


            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee(route('siswa.pemilihan.index'), false);
    }

    public function test_halaman_presensi_guru_tidak_menawarkan_pemilihan(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_panitia_user']))


            ->get(route('guru.presensi.index'))
            ->assertOk()
            ->assertDontSee(route('kelola.pemilihan.index'), false);
    }
}
