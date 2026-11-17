<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * Halaman login baru bergaya sama dengan fitur voting.
 * Yang diuji bukan hanya tampilan, tetapi juga bahwa login benar-benar berfungsi.
 */
class LoginTampilanTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_halaman_login_tampil(): void
    {
        $this->seedPemilihanDraf();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('SI-SMANSAKA')
            ->assertSee('Selamat datang')
            ->assertSee('name="username"', false)
            ->assertSee('name="password"', false);
    }

    public function test_halaman_login_punya_tautan_kembali_ke_beranda(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('beranda'), false);
    }

    public function test_halaman_login_tidak_memampang_kredensial_contoh(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        // Halaman publik tidak boleh membocorkan akun atau password contoh.
        $this->assertStringNotContainsString('Akun contoh', $html);
        $this->assertStringNotContainsString('Password semuanya', $html);
        $this->assertStringNotContainsString('<code>admin</code>', $html);
        $this->assertStringNotContainsString('<code>siswa1</code>', $html);
    }

    public function test_login_berhasil_dengan_akun_siswa(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $username = \Illuminate\Support\Facades\DB::connection('mysql')
            ->table('users')->where('id_user', $d['id_siswa_user'][0])->value('username');

        $this->post(route('login.proses'), [
            'username' => $username,
            'password' => 'password',
        ])->assertRedirect(route('siswa.presensi.riwayat'));

        $this->assertAuthenticatedAs($this->userDari($d['id_siswa_user'][0]));
    }

    public function test_login_gagal_menampilkan_pesan_error(): void
    {
        $this->seedPemilihanDraf();

        $this->from(route('login'))
            ->post(route('login.proses'), [
                'username' => 'tidak_ada',
                'password' => 'salah',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_halaman_login_menampilkan_pesan_error_setelah_gagal(): void
    {
        $this->seedPemilihanDraf();

        $this->from(route('login'))
            ->post(route('login.proses'), ['username' => 'salah', 'password' => 'salah']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Username atau password salah');
    }

    public function test_username_lama_tetap_terisi_setelah_gagal(): void
    {
        $this->seedPemilihanDraf();

        $this->from(route('login'))
            ->post(route('login.proses'), ['username' => 'salah_total', 'password' => 'salah']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('value="salah_total"', false);
    }

    public function test_pengguna_yang_sudah_login_tidak_bisa_buka_halaman_login(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('login'))
            ->assertRedirect();
    }
}
