<?php

namespace Tests\Feature;

use App\Enums\KategoriKas;
use App\Enums\JenisKas;
use App\Enums\Role;
use App\Enums\StatusIuran;
use App\Models\AnggotaKelas;
use App\Models\IuranKas;
use App\Models\Siswa;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

class BendaharaKasTest extends TestCase
{
    use MembuatSekolah, RefreshDatabase;


    private array $sekolah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->sekolah = $this->buatSekolah();
    }

    public function test_peran_memakai_beranda_siswa(): void
    {
        $this->assertSame('siswa.beranda', Role::BendaharaKas->home());
        $this->assertSame('siswa.beranda', Role::Siswa->home());
        $this->assertTrue(Role::BendaharaKas->punyaProfilSiswa());
    }

    public function test_bendahara_kas_dapat_membuka_halaman_kas(): void
    {
        $this->actingAs($this->bendaharaKas())
            ->get('/kas')
            ->assertOk();
    }

    public function test_bendahara_kas_dapat_mencatat_transaksi_kelasnya(): void
    {
        $this->actingAs($this->bendaharaKas())
            ->postJson('/kas/transaksi', [
                'id_kelas' => $this->sekolah['kelas']->id_kelas,
                'tanggal' => now()->toDateString(),
                'jenis' => JenisKas::Pemasukan->value,
                'kategori' => KategoriKas::Donasi->value,
                'nominal' => 50000,
                'keterangan' => 'Donasi orang tua',
            ])
            ->assertCreated();

        $this->assertSame(1, TransaksiKas::count());
    }

    public function test_bendahara_kas_dapat_membatalkan_transaksi(): void
    {
        $user = $this->bendaharaKas();

        $transaksi = TransaksiKas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'jenis' => JenisKas::Pengeluaran->value,
            'kategori' => KategoriKas::Konsumsi->value,
            'nominal' => 15000,
            'tanggal' => now()->toDateString(),
            'keterangan' => 'Salah catat',
            'dicatat_oleh' => $user->id_user,
        ]);

        $this->actingAs($user)
            ->deleteJson('/kas/transaksi/'.$transaksi->id_transaksi, ['alasan_batal' => 'Salah catat nominal'])
            ->assertOk();

        $this->assertTrue($transaksi->fresh()->is_batal);
    }

    public function test_bendahara_kas_dapat_memverifikasi_iuran(): void
    {
        $user = $this->bendaharaKas();
        $siswa = $user->siswa;

        $iuran = IuranKas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'id_siswa' => $siswa->id_siswa,
            'periode' => now()->format('Y-m'),
            'nominal' => 20000,
            'status' => StatusIuran::Menunggu,
        ]);

        $this->actingAs($user)
            ->patchJson('/kas/iuran/'.$iuran->id_iuran.'/verifikasi', ['status' => 'terverifikasi'])
            ->assertOk();

        $this->assertSame(StatusIuran::Terverifikasi, $iuran->fresh()->status);
        $this->assertNotNull($iuran->fresh()->id_transaksi);
    }

    public function test_bendahara_kas_tidak_dapat_menulis_ke_kelas_lain(): void
    {
        $kelasLain = $this->sekolah['kelasLain'];

        $this->actingAs($this->bendaharaKas())
            ->postJson('/kas/transaksi', [
                'id_kelas' => $kelasLain->id_kelas,
                'tanggal' => now()->toDateString(),
                'jenis' => JenisKas::Pemasukan->value,
                'kategori' => KategoriKas::Donasi->value,
                'nominal' => 10000,
                'keterangan' => 'Coba kelas lain',
            ])
            ->assertForbidden();

        $this->assertSame(0, TransaksiKas::count());
    }

    public function test_bendahara_kas_melihat_seluruh_baris_matriks_kelasnya(): void
    {
        $payload = $this->actingAs($this->bendaharaKas())
            ->getJson('/kas/data?id_kelas='.$this->sekolah['kelas']->id_kelas)
            ->assertOk()
            ->json();



        $this->assertCount(5, $payload['matriks']['siswa']);
        $this->assertTrue($payload['bisaKelola']);
    }

    public function test_bendahara_kas_tetap_dapat_fitur_siswa_lain(): void
    {
        $user = $this->bendaharaKas();

        $this->actingAs($user)->get('/denah')->assertOk();
        $this->actingAs($user)->get('/perangkat')->assertOk();
    }

    public function test_bendahara_kas_bukan_admin_dan_tidak_masuk_panel_admin(): void
    {
        $user = $this->bendaharaKas();

        $this->actingAs($user)->get('/admin/kas')->assertForbidden();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/akun')->assertForbidden();
    }

    public function test_bendahara_kas_tidak_dapat_mengelola_ruangan_denah(): void
    {
        $this->actingAs($this->bendaharaKas())
            ->postJson('/denah/ruangan', [
                'kode_ruangan' => 'R-999',
                'nama_ruangan' => 'Ruang Uji',
                'kategori' => 'kelas',
                'gedung' => 'Gedung Utama',
                'lantai' => 1,
                'posisi_x' => 1,
                'posisi_y' => 1,
                'lebar_grid' => 2,
                'tinggi_grid' => 2,
                'status' => 'tersedia',
            ])
            ->assertForbidden();
    }


    private function bendaharaKas(): User
    {
        $user = User::create([
            'username' => 'bendaharakas',
            'password_hash' => 'password',
            'role' => Role::BendaharaKas,
            'is_active' => true,
        ]);


        $profil = Siswa::create([
            'id_user' => $user->id_user,
            'nis' => '2026099',
            'nisn' => '0061234099',
            'nama_siswa' => 'Petugas Kas',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        AnggotaKelas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'id_siswa' => $profil->id_siswa,
            'no_absen' => 5,
        ]);

        return $user->fresh();
    }
}
