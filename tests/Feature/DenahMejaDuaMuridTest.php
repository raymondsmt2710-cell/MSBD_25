<?php

namespace Tests\Feature;

use App\Models\DenahKelas;
use App\Models\PosisiDuduk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

/**
 * Aturan baru: satu meja menampung 2 murid (sisi kiri & kanan).
 */
class DenahMejaDuaMuridTest extends TestCase
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

    public function test_satu_meja_dapat_diisi_dua_murid(): void
    {
        $denah = $this->buatDenah();

        $this->tempatkan($denah, $this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri')->assertOk();
        $this->tempatkan($denah, $this->sekolah['siswa'][1]->id_siswa, 1, 1, 'kanan')->assertOk();

        $this->assertSame(2, PosisiDuduk::where('baris', 1)->where('kolom', 1)->count());
    }

    public function test_sisi_yang_sama_tidak_boleh_diisi_dua_murid(): void
    {
        $denah = $this->buatDenah();

        $this->tempatkan($denah, $this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri')->assertOk();

        $this->tempatkan($denah, $this->sekolah['siswa'][1]->id_siswa, 1, 1, 'kiri')
            ->assertStatus(422);

        $this->assertSame(1, PosisiDuduk::where('baris', 1)->where('kolom', 1)->count());
    }

    public function test_meja_penuh_menolak_murid_ketiga(): void
    {
        $denah = $this->buatDenah();

        $this->tempatkan($denah, $this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri')->assertOk();
        $this->tempatkan($denah, $this->sekolah['siswa'][1]->id_siswa, 1, 1, 'kanan')->assertOk();

        // Tidak ada sisi tersisa -> ditolak
        $this->tempatkan($denah, $this->sekolah['siswa'][2]->id_siswa, 1, 1, 'kiri')
            ->assertStatus(422);
    }

    public function test_acak_mengisi_dua_murid_per_meja(): void
    {
        $this->buatDenah();

        $this->actingAs($this->sekolah['ketua'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/acak')
            ->assertOk();

        // 4 murid, 2 per meja -> 2 meja terisi
        $perMeja = PosisiDuduk::all()->groupBy(fn ($p) => $p->baris.'-'.$p->kolom);

        $this->assertCount(2, $perMeja);

        foreach ($perMeja as $rows) {
            $this->assertCount(2, $rows, 'Setiap meja harus berisi tepat 2 murid.');
            $this->assertEqualsCanonicalizing(['kiri', 'kanan'], $rows->pluck('sisi')->all());
        }
    }

    public function test_kapasitas_acak_menghitung_dua_sisi_per_meja(): void
    {
        $denah = $this->buatDenah();

        // Perkecil grid: 1 baris x 2 kolom = 4 tempat (2 meja x 2 sisi).
        $denah->update(['jumlah_baris' => 1, 'jumlah_kolom' => 2]);

        // 4 murid masih muat.
        $this->actingAs($this->sekolah['ketua'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/acak')
            ->assertOk();

        $this->assertSame(4, PosisiDuduk::count());
    }

    public function test_pindah_ke_sisi_kosong_berhasil(): void
    {
        $denah = $this->buatDenah();

        $this->tempatkan($denah, $this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri')->assertOk();
        $posisi = PosisiDuduk::firstOrFail();

        $this->actingAs($this->sekolah['ketua'])
            ->patchJson("/denah/posisi/{$posisi->id_posisi}", [
                'baris' => 2,
                'kolom' => 2,
                'sisi' => 'kanan',
            ])
            ->assertOk();

        $this->assertDatabaseHas('posisi_duduk', [
            'id_posisi' => $posisi->id_posisi,
            'baris' => 2,
            'kolom' => 2,
            'sisi' => 'kanan',
        ]);
    }

    public function test_data_denah_mengembalikan_dua_murid_per_meja(): void
    {
        $denah = $this->buatDenah();

        $this->tempatkan($denah, $this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri')->assertOk();
        $this->tempatkan($denah, $this->sekolah['siswa'][1]->id_siswa, 1, 1, 'kanan')->assertOk();

        $response = $this->actingAs($this->sekolah['ketua'])
            ->getJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/data')
            ->assertOk();

        $meja = collect($response->json('kursi'))->firstWhere('baris', 1)['murid'] ?? null;

        $this->assertNotNull($meja);
        $this->assertSame($this->sekolah['siswa'][0]->id_siswa, $meja['kiri']['siswa']['id_siswa']);
        $this->assertSame($this->sekolah['siswa'][1]->id_siswa, $meja['kanan']['siswa']['id_siswa']);
    }

    private function tempatkan(DenahKelas $denah, int $idSiswa, int $baris, int $kolom, string $sisi)
    {
        return $this->actingAs($this->sekolah['ketua'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/tempatkan', [
                'id_siswa' => $idSiswa,
                'baris' => $baris,
                'kolom' => $kolom,
                'sisi' => $sisi,
            ]);
    }

    private function buatDenah(): DenahKelas
    {
        return DenahKelas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'jumlah_baris' => 6,
            'jumlah_kolom' => 4,
            'posisi_meja_guru' => 'depan_tengah',
            'posisi_pintu' => 'kanan',
            'berlaku_mulai' => now()->toDateString(),
            'dibuat_oleh' => $this->sekolah['admin']->id_user,
            'is_aktif' => true,
        ]);
    }
}
