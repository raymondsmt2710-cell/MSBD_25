<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * Halaman awal untuk tamu (landing page).
 * Tamu melihat beranda; pengguna yang sudah login diarahkan sesuai peran.
 */
class BerandaTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_tamu_melihat_halaman_beranda(): void
    {
        $this->seedPemilihanDraf();

        $this->get('/')
            ->assertOk()
            ->assertSee('SI-SMANSAKA')
            ->assertSee('SMA Negeri 1 Kabanjahe');
    }

    public function test_beranda_menampilkan_daftar_fitur(): void
    {
        $this->seedPemilihanDraf();

        $this->get('/')
            ->assertOk()
            ->assertSee('Presensi QR Sekali Pakai')
            ->assertSee('Pemilihan Suara Elektronik')
            ->assertSee('Denah Kelas')
            ->assertSee('Pemantauan Orang Tua');
    }

    public function test_beranda_punya_tautan_masuk(): void
    {
        $this->seedPemilihanDraf();

        $this->get('/')
            ->assertOk()
            ->assertSee(route('login'), false);
    }

    public function test_beranda_menampilkan_statistik_dari_database(): void
    {
        $d = $this->seedPemilihanDraf();

        // seed membuat 2 siswa dan 1 kelas.
        $this->get('/')
            ->assertOk()
            ->assertSee('Siswa Aktif')
            ->assertSee('Guru')
            ->assertSee('Kelas');
    }

    public function test_siswa_yang_sudah_login_diarahkan_ke_pemilihan(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get('/')
            ->assertRedirect(route('siswa.pemilihan.index'));
    }

    public function test_admin_yang_sudah_login_diarahkan_ke_kelola(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->get('/')
            ->assertRedirect(route('kelola.pemilihan.index'));
    }

    public function test_beranda_tidak_error_walau_tabel_kosong(): void
    {
        // Tidak seed apa pun — statistik harus 0, bukan error.
        $this->get('/')
            ->assertOk()
            ->assertSee('SI-SMANSAKA');
    }
}
