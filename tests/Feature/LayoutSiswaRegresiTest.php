<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LayoutSiswaRegresiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(DBDynamicConnection::class);
    }

    private function buatSiswa(): \App\Models\User
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => 'siswa_layout',
            'password_hash' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        DB::table('siswa')->insert([
            'id_user' => $idUser,
            'nis' => '20260199',
            'nisn' => '0020260199',
            'nama_siswa' => 'Siswa Layout',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        return \App\Models\User::find($idUser);
    }

    public function test_judul_halaman_perangkat_tidak_tertimpa_judul_layout(): void
    {


        $user = $this->buatSiswa();

        $html = $this->actingAs($user)
            ->get(route('siswa.perangkat'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Perangkat Kelas &amp; Organisasi', $html);
    }

    public function test_link_brand_mengarah_ke_beranda_siswa(): void
    {
        $user = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('href="'.route('siswa.beranda').'" class="brand"', false);
    }

    public function test_nav_siswa_tetap_lengkap_empat_tautan(): void
    {
        $user = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee(route('siswa.beranda'), false)
            ->assertSee(route('siswa.presensi.riwayat'), false)
            ->assertSee(route('siswa.perangkat'), false)
            ->assertSee(route('siswa.pemilihan.index'), false);
    }


    public function test_nav_siswa_memuat_tautan_keuangan_kelas(): void
    {
        $user = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('Keuangan Kelas')
            ->assertSee(route('kas.index'), false);
    }
}
