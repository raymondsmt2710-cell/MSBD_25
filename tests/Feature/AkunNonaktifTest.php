<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Akun yang dinonaktifkan (users.is_active = 0) harus langsung kehilangan akses,
// termasuk lewat session yang sudah terbuka.
//
// Login memang memeriksa is_active, tetapi session lama tidak pernah diperiksa ulang.
// Akibatnya admin yang menonaktifkan akun tidak benar-benar mencabut akses sampai
// user tersebut logout sendiri.
//
// PENTING: tes ini masuk lewat POST /login yang sesungguhnya, bukan actingAs().
// actingAs() menanam instance model yang sudah dimuat ke memori, sehingga perubahan
// is_active di database tidak terlihat oleh tes — padahal di produksi session guard
// selalu mengambil ulang user dari database pada setiap request. Memakai login asli
// membuat tes mengukur perilaku yang sama dengan produksi.
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

    /** Login sungguhan lewat POST /login, sehingga session menyimpan id user (bukan objek model). */
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

        // Laravel menguji banyak request dalam SATU instance aplikasi, sehingga guard
        // menyimpan objek user yang sudah dimuat di memori. Di produksi setiap request
        // di-boot ulang (dan di Octane dipanggil forgetGuards()), jadi user SELALU
        // diambil ulang dari database. Baris ini menyamakan kedua kondisi itu —
        // tanpa ini, middleware membaca is_active yang sudah basi dan tes memberi
        // hasil palsu (lulus padahal produksi tidak).
        Auth::forgetGuards();
    }

    public function test_session_lama_akun_nonaktif_ditolak_dan_dilogout(): void
    {
        $user = $this->pengguna('siswa');

        // Session dibuka saat akun masih aktif.
        $this->loginSebagai($user->username);

        // Admin menonaktifkan akun.
        $this->nonaktifkan($user);

        $this->get('/dashboard/presensi')->assertRedirect(route('login'));

        // Session benar-benar dicabut, bukan hanya dialihkan.
        $this->assertGuest('web');
    }

    public function test_akun_nonaktif_tidak_bisa_akses_halaman_berperan(): void
    {
        $user = $this->pengguna('orang_tua');

        $this->loginSebagai($user->username);
        $this->nonaktifkan($user);

        $this->get('/ortu')->assertRedirect(route('login'));
    }

    // Akun yang masih aktif tidak boleh ikut ter-logout.
    public function test_akun_aktif_tetap_bisa_akses(): void
    {
        $user = $this->pengguna('siswa');

        $this->loginSebagai($user->username);

        // Akun uji tanpa profil siswa boleh saja ditolak halaman (403), yang penting tidak dilogout.
        $respons = $this->get('/dashboard/presensi');
        $this->assertNotSame(route('login'), $respons->headers->get('Location'));

        $this->assertAuthenticatedAs($user->fresh());
    }
}
