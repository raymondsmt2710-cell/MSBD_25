<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * Regresi navigasi: halaman "/" dulu menampilkan welcome bawaan Laravel yang
 * tidak punya tautan ke fitur mana pun, dan tombol "Dashboard" mengarah ke
 * route /dashboard yang tidak ada (404).
 */
class NavigasiTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_tamu_melihat_landing_page_dari_beranda(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('SI-SMANSAKA')
            ->assertSee(route('login'), false);
    }

    public function test_siswa_diarahkan_ke_halaman_pemilihan_dari_beranda(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get('/')
            ->assertRedirect(route('siswa.pemilihan.index'));
    }

    public function test_admin_diarahkan_ke_kelola_pemilihan_dari_beranda(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->get('/')
            ->assertRedirect(route('kelola.pemilihan.index'));
    }

    public function test_route_dashboard_tidak_dipakai_lagi(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('dashboard'));
    }

    public function test_halaman_presensi_siswa_punya_menu_pemilihan(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('siswa.presensi.riwayat'))
            ->assertOk()
            ->assertSee(route('siswa.pemilihan.index'), false);
    }

    public function test_halaman_presensi_guru_tidak_menawarkan_menu_pemilihan(): void
    {
        // Guru tidak punya akses voting; tautan Pemilihan tidak boleh ditawarkan
        // di halaman presensi guru.
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_panitia_user']))
            ->get(route('guru.presensi.index'))
            ->assertOk()
            ->assertDontSee(route('kelola.pemilihan.index'), false);
    }
}
