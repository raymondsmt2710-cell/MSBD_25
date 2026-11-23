<?php

namespace Tests\Feature;

use App\Enums\StatusRuangan;
use App\Models\DenahKelas;
use App\Models\PosisiDuduk;
use App\Models\RuanganSekolah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

/**
 * Denah hanya boleh diubah administrator.
 *
 * Sebelumnya ketua kelas juga boleh memindah, menukar, mengacak, dan
 * mengatur ukuran denah. Sekarang seluruh peran selain admin hanya-baca,
 * sementara halaman denah tetap dapat dilihat semua pihak terkait.
 */
class DenahHanyaAdminTest extends TestCase
{
    use MembuatSekolah, RefreshDatabase;

    /** @var array<string, mixed> */
    private array $sekolah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->sekolah = $this->buatSekolah();
    }

    // ---- Yang boleh: administrator ----

    public function test_admin_dapat_mengacak_posisi(): void
    {
        $this->buatDenah();

        $this->actingAs($this->sekolah['admin'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/acak')
            ->assertOk();
    }

    public function test_admin_melihat_tombol_kelola(): void
    {
        $payload = $this->actingAs($this->sekolah['admin'])
            ->getJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/data')
            ->assertOk()
            ->json();

        $this->assertTrue($payload['bisa_kelola']);
    }

    // ---- Yang tidak boleh: ketua kelas ----

    public function test_ketua_kelas_tidak_boleh_mengacak_posisi(): void
    {
        $this->buatDenah();

        $this->actingAs($this->sekolah['ketua'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/acak')
            ->assertForbidden();
    }

    public function test_ketua_kelas_tidak_boleh_mengubah_ukuran_denah(): void
    {
        $denah = $this->buatDenah();

        $this->actingAs($this->sekolah['ketua'])
            ->putJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas, [
                'jumlah_baris' => 4,
                'jumlah_kolom' => 3,
                'posisi_meja_guru' => 'depan_tengah',
                'posisi_pintu' => 'kanan',
            ])
            ->assertForbidden();

        $this->assertSame(2, $denah->fresh()->jumlah_baris);
    }

    public function test_ketua_kelas_tidak_boleh_memindah_kursi(): void
    {
        $denah = $this->buatDenah();
        $posisi = PosisiDuduk::where('id_denah', $denah->id_denah)->firstOrFail();

        $this->actingAs($this->sekolah['ketua'])
            ->patchJson('/denah/posisi/'.$posisi->id_posisi, ['baris' => 2, 'kolom' => 2, 'sisi' => 'kanan'])
            ->assertForbidden();

        $this->assertSame(1, $posisi->fresh()->baris);
    }

    public function test_ketua_kelas_tidak_boleh_menukar_posisi(): void
    {
        $denah = $this->buatDenah();
        $semua = PosisiDuduk::where('id_denah', $denah->id_denah)->orderBy('id_posisi')->get();

        $this->actingAs($this->sekolah['ketua'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/swap', [
                'posisi_a' => $semua[0]->id_posisi,
                'posisi_b' => $semua[1]->id_posisi,
            ])
            ->assertForbidden();
    }

    public function test_ketua_kelas_tidak_boleh_menempatkan_siswa(): void
    {
        $this->buatDenah();

        $this->actingAs($this->sekolah['ketua'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/tempatkan', [
                'id_siswa' => $this->sekolah['siswa'][3]->id_siswa,
                'baris' => 2,
                'kolom' => 2,
                'sisi' => 'kiri',
            ])
            ->assertForbidden();
    }

    public function test_ketua_kelas_masih_dapat_melihat_denah(): void
    {
        $this->buatDenah();

        $payload = $this->actingAs($this->sekolah['ketua'])
            ->getJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/data')
            ->assertOk()
            ->json();

        $this->assertFalse($payload['bisa_kelola']);
        // Endpoint mengembalikan daftar MEJA, bukan murid: 4 murid di 2 meja.
        $this->assertCount(2, $payload['kursi']);
    }

    // ---- Yang tidak boleh: siswa biasa & bendahara kas ----

    public function test_siswa_biasa_tidak_boleh_mengubah_denah(): void
    {
        $this->buatDenah();

        $this->actingAs($this->sekolah['anggota'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/acak')
            ->assertForbidden();

        $this->actingAs($this->sekolah['anggota'])
            ->get('/denah')
            ->assertOk();
    }

    // ---- Ruangan: tetap hanya admin (sudah begitu sejak awal) ----

    public function test_ketua_kelas_tidak_boleh_menambah_ruangan(): void
    {
        $this->actingAs($this->sekolah['ketua'])
            ->postJson('/denah/ruangan', $this->payloadRuangan())
            ->assertForbidden();
    }

    // ---- helper ----

    private function buatDenah(): DenahKelas
    {
        $denah = DenahKelas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'jumlah_baris' => 2,
            'jumlah_kolom' => 2,
            'posisi_meja_guru' => 'depan_tengah',
            'posisi_pintu' => 'kanan',
            'berlaku_mulai' => now()->toDateString(),
            'dibuat_oleh' => $this->sekolah['admin']->id_user,
            'is_aktif' => true,
        ]);

        $posisi = [[1, 1, 'kiri'], [1, 1, 'kanan'], [2, 2, 'kiri'], [2, 2, 'kanan']];

        foreach ($this->sekolah['siswa'] as $index => $siswa) {
            PosisiDuduk::create([
                'id_denah' => $denah->id_denah,
                'id_siswa' => $siswa->id_siswa,
                'baris' => $posisi[$index][0],
                'kolom' => $posisi[$index][1],
                'sisi' => $posisi[$index][2],
            ]);
        }

        return $denah;
    }

    /** @return array<string, mixed> */
    private function payloadRuangan(): array
    {
        return [
            'kode_ruangan' => 'R-777',
            'nama_ruangan' => 'Ruang Uji',
            'kategori' => 'kelas',
            'gedung' => 'Gedung Utama',
            'lantai' => 1,
            'posisi_x' => 1,
            'posisi_y' => 1,
            'lebar_grid' => 2,
            'tinggi_grid' => 2,
            'status' => StatusRuangan::Tersedia->value,
        ];
    }
}
