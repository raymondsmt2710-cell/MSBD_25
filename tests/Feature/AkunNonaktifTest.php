<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AkunNonaktifTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(DBDynamicConnection::class);
    }

    private function pengguna(string $role): User
    {
        $id = DB::table('users')->insertGetId([
            'username' => 'uji_aktif_'.$role,
            'password_hash' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
        ]);

        return User::find($id);
    }


    private function loginSebagai(string $username): void
    {
        $this->post('/login', [
            'username' => $username,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticated();
    }

    private function nonaktifkan(User $user): void
    {
        DB::table('users')->where('id_user', $user->id_user)->update(['is_active' => false]);







        Auth::forgetGuards();
    }

    public function test_session_lama_akun_nonaktif_ditolak_dan_dilogout(): void
    {
        $user = $this->pengguna('siswa');


        $this->loginSebagai($user->username);


        $this->nonaktifkan($user);

        $this->get('/siswa/beranda/presensi')->assertRedirect(route('login'));


        $this->assertGuest('web');
    }

    public function test_akun_nonaktif_tidak_bisa_akses_halaman_berperan(): void
    {
        $user = $this->pengguna('orang_tua');

        $this->loginSebagai($user->username);
        $this->nonaktifkan($user);

        $this->get('/ortu')->assertRedirect(route('login'));
    }


    public function test_akun_aktif_tetap_bisa_akses(): void
    {
        $user = $this->pengguna('siswa');

        $this->loginSebagai($user->username);


        $respons = $this->get('/siswa/beranda/presensi');
        $this->assertNotSame(route('login'), $respons->headers->get('Location'));

        $this->assertAuthenticatedAs($user->fresh());
    }
}
