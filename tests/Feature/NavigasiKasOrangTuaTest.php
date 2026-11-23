<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\OrangTua;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

class NavigasiKasOrangTuaTest extends TestCase
{
    use MembuatSekolah, RefreshDatabase;


    private array $sekolah;

    private User $orangTua;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->sekolah = $this->buatSekolah();
        $this->orangTua = $this->buatOrangTua($this->sekolah['siswa'][0]->id_siswa);
    }

    public function test_portal_orang_tua_menampilkan_menu_keuangan_anak(): void
    {
        $this->actingAs($this->orangTua)
            ->get(route('ortu.index'))
            ->assertOk()
            ->assertSee('Keuangan Anak')
            ->assertSee(route('kas.index'), false);
    }

    public function test_detail_anak_punya_tombol_menuju_kas(): void
    {
        $this->actingAs($this->orangTua)
            ->get(route('ortu.anak', $this->sekolah['siswa'][0]->id_siswa))
            ->assertOk()
            ->assertSee('Buka Keuangan Anak');
    }

    public function test_orang_tua_dapat_membuka_halaman_kas_dari_portal(): void
    {
        $this->actingAs($this->orangTua)
            ->get('/kas')
            ->assertOk()
            ->assertSee('Keuangan Kelas');
    }

    public function test_orang_tua_tidak_melihat_menu_kas_milik_peran_lain(): void
    {


        $this->actingAs($this->orangTua)
            ->get(route('ortu.index'))
            ->assertOk()
            ->assertDontSee('Mode Edit');
    }

    private function buatOrangTua(int $idSiswa): User
    {
        $user = User::create([
            'username' => 'ortu_nav',
            'password_hash' => 'password',
            'role' => Role::OrangTua,
            'is_active' => true,
        ]);

        $orangTua = OrangTua::create([
            'id_user' => $user->id_user,
            'nama' => 'Wali Nav',
        ]);

        $orangTua->siswa()->attach($idSiswa, ['hubungan' => 'wali', 'is_kontak_utama' => true]);

        return $user->fresh();
    }
}
