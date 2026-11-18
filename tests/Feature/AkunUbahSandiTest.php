<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Halaman /akun: ganti sandi mandiri.
 *
 * Tes ini berjalan di SQLite (lihat phpunit.xml). Di MySQL, penulisan sandi
 * lewat procedure sp_ubah_sandi; di SQLite procedure tidak ada sehingga
 * controller memakai jalur Eloquent. Yang diuji di sini adalah aturan bisnisnya:
 * verifikasi sandi lama, panjang minimal, konfirmasi, dan sesi tetap hidup.
 * Keberadaan procedure + GRANT diuji terpisah di HakEksekusiRoutineTest.
 */
class AkunUbahSandiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(DBDynamicConnection::class);
    }

    private function buatAkun(string $role = 'siswa', string $username = 'akun_uji'): User
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => $username,
            'password_hash' => Hash::make('sandi-lama'),
            'role' => $role,
            'is_active' => true,
        ]);

        return User::find($idUser);
    }

    /** Melengkapi akun dengan baris siswa agar data diri punya isi. */
    private function lengkapiSiswa(User $user, array $ubah = []): void
    {
        DB::table('siswa')->insert(array_merge([
            'id_user' => $user->id_user,
            'nis' => '20260123',
            'nisn' => '0020260123',
            'nama_siswa' => 'Siswa Data Diri',
            'jenis_kelamin' => 'P',
            'status' => 'aktif',
        ], $ubah));
    }

    /** Melengkapi akun dengan baris guru agar data diri punya isi. */
    private function lengkapiGuru(User $user, array $ubah = []): void
    {
        DB::table('guru')->insert(array_merge([
            'id_user' => $user->id_user,
            'nip' => '1988045',
            'nama_guru' => 'Guru Data Diri',
            'jenis_kelamin' => 'L',
        ], $ubah));
    }

    public function test_siswa_melihat_data_diri_di_halaman_akun(): void
    {
        // Data diri dulu ada di dashboard. Setelah dashboard dirampingkan, tempatnya
        // di sini — jadi halaman ini yang harus membuktikannya masih terbaca.
        $user = $this->buatAkun('siswa', 'siswa_diri');
        $this->lengkapiSiswa($user);

        $this->actingAs($user)
            ->get(route('akun.profil'))
            ->assertOk()
            ->assertSee('Data Diri')
            ->assertSee('Siswa Data Diri')
            ->assertSee('20260123')
            ->assertSee('0020260123');
    }

    public function test_guru_melihat_data_diri_di_halaman_akun(): void
    {
        $user = $this->buatAkun('guru', 'guru_diri');
        $this->lengkapiGuru($user);

        $this->actingAs($user)
            ->get(route('akun.profil'))
            ->assertOk()
            ->assertSee('Data Diri')
            ->assertSee('Guru Data Diri')
            ->assertSee('1988045')
            // Guru tidak boleh melihat data siswa.
            ->assertDontSee('NISN');
    }

    public function test_siswa_tanpa_kelas_menampilkan_strip_bukan_error(): void
    {
        // Siswa baru belum punya baris anggota_kelas. Kolom Kelas harus "—",
        // bukan halaman 500 atau teks membingungkan.
        $user = $this->buatAkun('siswa', 'siswa_tanpa_kelas');
        $this->lengkapiSiswa($user, ['nama_siswa' => 'Siswa Tanpa Kelas']);

        $this->actingAs($user)
            ->get(route('akun.profil'))
            ->assertOk()
            ->assertSee('Siswa Tanpa Kelas')
            ->assertSee('—');
    }

    public function test_admin_dan_orang_tua_tidak_melihat_bagian_data_diri(): void
    {
        // Peran ini tidak punya baris siswa/guru, jadi bagian Data Diri tidak dirender
        // sama sekali (bukan dirender kosong).
        foreach (['admin', 'orang_tua'] as $role) {
            $user = $this->buatAkun($role, 'diri_'.$role);

            $this->actingAs($user)
                ->get(route('akun.profil'))
                ->assertOk()
                ->assertSee('Informasi Akun')
                ->assertDontSee('Data Diri');
        }
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('akun.profil'))->assertRedirect(route('login'));
    }

    public function test_siswa_bisa_membuka_halaman_akun(): void
    {
        $user = $this->buatAkun('siswa');

        $this->actingAs($user)
            ->get(route('akun.profil'))
            ->assertOk()
            ->assertSee('Ganti Sandi')
            ->assertSee($user->username);
    }

    public function test_guru_juga_bisa_membuka_halaman_akun(): void
    {
        // Halaman akun bukan fitur siswa saja: guru butuh jalan yang sama.
        $user = $this->buatAkun('guru', 'guru_akun');

        $this->actingAs($user)
            ->get(route('akun.profil'))
            ->assertOk()
            ->assertSee('Ganti Sandi')
            ->assertSee('Guru');
    }

    public function test_sandi_lama_salah_tidak_mengubah_apa_pun(): void
    {
        $user = $this->buatAkun();
        $hashAwal = $user->password_hash;

        $this->actingAs($user)
            ->from(route('akun.profil'))
            ->put(route('akun.sandi.simpan'), [
                'sandi_lama' => 'sandi-keliru',
                'sandi' => 'sandi-baru-123',
                'sandi_confirmation' => 'sandi-baru-123',
            ])
            ->assertRedirect(route('akun.profil'))
            ->assertSessionHasErrors('sandi_lama');

        $this->assertSame($hashAwal, $user->fresh()->password_hash);
    }

    public function test_konfirmasi_tidak_cocok_ditolak(): void
    {
        $user = $this->buatAkun();
        $hashAwal = $user->password_hash;

        $this->actingAs($user)
            ->from(route('akun.profil'))
            ->put(route('akun.sandi.simpan'), [
                'sandi_lama' => 'sandi-lama',
                'sandi' => 'sandi-baru-123',
                'sandi_confirmation' => 'beda-sendiri',
            ])
            ->assertSessionHasErrors('sandi');

        $this->assertSame($hashAwal, $user->fresh()->password_hash);
    }

    public function test_sandi_baru_kurang_dari_delapan_karakter_ditolak(): void
    {
        $user = $this->buatAkun();
        $hashAwal = $user->password_hash;

        $this->actingAs($user)
            ->from(route('akun.profil'))
            ->put(route('akun.sandi.simpan'), [
                'sandi_lama' => 'sandi-lama',
                'sandi' => 'pendek',
                'sandi_confirmation' => 'pendek',
            ])
            ->assertSessionHasErrors('sandi');

        $this->assertSame($hashAwal, $user->fresh()->password_hash);
    }

    public function test_sandi_berhasil_diubah(): void
    {
        $user = $this->buatAkun();
        $hashAwal = $user->password_hash;

        $this->actingAs($user)
            ->from(route('akun.profil'))
            ->put(route('akun.sandi.simpan'), [
                'sandi_lama' => 'sandi-lama',
                'sandi' => 'sandi-baru-123',
                'sandi_confirmation' => 'sandi-baru-123',
            ])
            ->assertRedirect(route('akun.profil'))
            ->assertSessionHas('success');

        $hashBaru = $user->fresh()->password_hash;

        $this->assertNotSame($hashAwal, $hashBaru);
        $this->assertTrue(Hash::check('sandi-baru-123', $hashBaru));
        $this->assertFalse(Hash::check('sandi-lama', $hashBaru));
    }

    public function test_pengguna_tetap_login_setelah_mengganti_sandi(): void
    {
        $user = $this->buatAkun();

        $this->actingAs($user)
            ->from(route('akun.profil'))
            ->put(route('akun.sandi.simpan'), [
                'sandi_lama' => 'sandi-lama',
                'sandi' => 'sandi-baru-123',
                'sandi_confirmation' => 'sandi-baru-123',
            ]);

        // Sesi tidak diputus: mengganti sandi sendiri bukan alasan mengeluarkan
        // pengguna. Kalau ini pecah, pengguna terlempar ke login setelah simpan.
        $this->assertAuthenticatedAs($user->fresh());
        $this->get(route('akun.profil'))->assertOk();
    }

    public function test_halaman_akun_menampilkan_tombol_keluar(): void
    {
        $user = $this->buatAkun();

        $this->actingAs($user)
            ->get(route('akun.profil'))
            ->assertOk()
            ->assertSee(route('logout'), false)
            ->assertSee('Keluar');
    }

    public function test_tombol_keluar_hanya_ada_di_halaman_akun(): void
    {
        // Bar atas tidak lagi memuat tombol Keluar; satu-satunya jalan keluar
        // adalah halaman profil. Kalau ini pecah, pengguna terkunci.
        $siswa = $this->buatAkun('siswa', 'keluar_siswa');
        $this->lengkapiSiswa($siswa);

        $this->actingAs($siswa)
            ->get(route('akun.profil'))
            ->assertOk()
            ->assertSee(route('logout'), false);

        foreach ([route('siswa.beranda'), route('dashboard.presensi.index')] as $halaman) {
            $this->actingAs($siswa)
                ->get($halaman)
                ->assertOk()
                ->assertDontSee(route('logout'), false);
        }

        $admin = $this->buatAkun('admin', 'keluar_admin');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('logout'), false);
    }

    public function test_admin_dan_orang_tua_bisa_membuka_halaman_akun_tanpa_tautan_mati(): void
    {
        // Rute /akun hanya memakai middleware `auth`, jadi keempat peran bisa masuk.
        // Drawer Menu Cepat milik siswa berisi rute ber-role:siswa (perangkat,
        // pemilihan); kalau ikut dirender untuk admin/orang tua, tautannya 403.
        foreach (['admin' => 'Administrator', 'orang_tua' => 'Orang Tua'] as $role => $label) {
            $user = $this->buatAkun($role, 'akun_'.$role);

            $html = $this->actingAs($user)
                ->get(route('akun.profil'))
                ->assertOk()
                ->assertSee('Ganti Sandi')
                ->assertSee($label)
                ->getContent();

            // Tidak ada tautan ke fitur khusus siswa.
            $this->assertStringNotContainsString(route('siswa.pemilihan.index'), $html);
            $this->assertStringNotContainsString(route('siswa.perangkat'), $html);
            // Logo harus menuju beranda peran sendiri, bukan beranda siswa.
            $this->assertStringNotContainsString('href="'.route('siswa.beranda').'" class="brand"', $html);
        }
    }
}
