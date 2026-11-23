<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatusIuran;
use App\Models\IuranKas;
use App\Models\Siswa;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi: akun demo `bendahara` HARUS terbuat oleh `php artisan migrate --seed`.
 *
 * Sebelumnya akun ini hanya ada di DemoSekolahSeeder, yang tidak dipanggil
 * DatabaseSeeder. Akibatnya tim yang mengikuti langkah README tidak pernah
 * punya akun tersebut dan selalu gagal login ("Username atau password salah").
 *
 * Test ini menjalankan seeder sungguhan lalu memeriksa akunnya bisa dipakai.
 */
class AkunBendaharaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_standar_membuat_akun_bendahara(): void
    {
        $this->seed();

        $user = User::where('username', 'bendahara')->first();

        $this->assertNotNull($user, 'Akun bendahara tidak dibuat oleh db:seed.');
        $this->assertSame(Role::BendaharaKas, $user->role);
        $this->assertTrue((bool) $user->is_active);
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('password', $user->password_hash),
            'Sandi akun bendahara bukan "password".',
        );

        // Profil siswa wajib ada: tanpa ini peran tidak punya kelas.
        $this->assertInstanceOf(Siswa::class, $user->siswa);
    }

    public function test_akun_bendahara_hasil_seeder_dapat_mengelola_kas(): void
    {
        $this->seed();
        $this->withoutVite();

        $user = User::where('username', 'bendahara')->firstOrFail();

        $this->actingAs($user)
            ->get('/kas')
            ->assertOk();

        // Seeder sudah membuat transaksi contoh, jadi hitung selisihnya
        // dan cari berdasarkan keterangan yang unik.
        $sebelum = TransaksiKas::count();

        $this->actingAs($user)
            ->postJson('/kas/transaksi', [
                'id_kelas' => $user->siswa->anggotaKelas()->value('id_kelas'),
                'tanggal' => now()->toDateString(),
                'jenis' => 'pemasukan',
                'kategori' => 'Donasi',
                'nominal' => 25000,
                'keterangan' => 'Uji akun demo bendahara',
            ])
            ->assertCreated();

        $this->assertSame($sebelum + 1, TransaksiKas::count());
        $this->assertSame(
            1,
            TransaksiKas::where('keterangan', 'Uji akun demo bendahara')->count(),
        );
    }

    public function test_seeder_dapat_dijalankan_dua_kali_tanpa_menggandakan_akun(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, User::where('username', 'bendahara')->count());
    }
}
