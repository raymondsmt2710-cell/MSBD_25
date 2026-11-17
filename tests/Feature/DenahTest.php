<?php

namespace Tests\Feature;

use App\Models\DenahKelas;
use App\Models\PosisiDuduk;
use App\Models\RuanganSekolah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

class DenahTest extends TestCase
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

    public function test_siswa_biasa_dapat_melihat_denah(): void
    {
        $this->buatDenah();

        $this->actingAs($this->sekolah['anggota'])
            ->get('/denah')
            ->assertOk()
            ->assertSee('Denah Interaktif');
    }

    public function test_siswa_biasa_tidak_boleh_mengacak_posisi(): void
    {
        $this->buatDenah();

        $this->actingAs($this->sekolah['anggota'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/acak')
            ->assertForbidden();
    }

    public function test_ketua_kelas_dapat_mengacak_posisi(): void
    {
        $this->buatDenah();

        $this->actingAs($this->sekolah['ketua'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/acak')
            ->assertOk();

        $this->assertSame(4, PosisiDuduk::count());
    }

    public function test_tukar_posisi_berhasil(): void
    {
        $denah = $this->buatDenah();

        $a = PosisiDuduk::where('id_denah', $denah->id_denah)->where('baris', 1)->where('kolom', 1)->firstOrFail();
        $b = PosisiDuduk::where('id_denah', $denah->id_denah)->where('baris', 1)->where('kolom', 2)->firstOrFail();

        $this->actingAs($this->sekolah['ketua'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/swap', [
                'posisi_a' => $a->id_posisi,
                'posisi_b' => $b->id_posisi,
            ])
            ->assertOk();

        $this->assertDatabaseHas('posisi_duduk', [
            'id_posisi' => $a->id_posisi,
            'id_siswa' => $a->id_siswa,
            'baris' => 1,
            'kolom' => 2,
        ]);
        $this->assertDatabaseHas('posisi_duduk', [
            'id_posisi' => $b->id_posisi,
            'id_siswa' => $b->id_siswa,
            'baris' => 1,
            'kolom' => 1,
        ]);
    }

    public function test_menempatkan_siswa_yang_belum_duduk(): void
    {
        $denah = $this->buatDenah();

        PosisiDuduk::where('id_denah', $denah->id_denah)
            ->where('id_siswa', $this->sekolah['siswa'][3]->id_siswa)
            ->delete();

        $this->actingAs($this->sekolah['ketua'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/tempatkan', [
                'id_siswa' => $this->sekolah['siswa'][3]->id_siswa,
                'baris' => 2,
                'kolom' => 1,
            ])
            ->assertOk();

        $this->assertDatabaseHas('posisi_duduk', [
            'id_denah' => $denah->id_denah,
            'id_siswa' => $this->sekolah['siswa'][3]->id_siswa,
            'baris' => 2,
            'kolom' => 1,
        ]);
    }

    public function test_admin_dapat_menambah_dan_menghapus_ruangan(): void
    {
        $payload = $this->payloadRuangan();

        $response = $this->actingAs($this->sekolah['admin'])
            ->postJson('/denah/ruangan', $payload);

        $response->assertCreated();

        $id = $response->json('ruangan.id_ruangan');

        $this->actingAs($this->sekolah['admin'])
            ->patchJson("/denah/ruangan/{$id}/status", ['status' => 'maintenance'])
            ->assertOk();

        $this->assertSame('maintenance', RuanganSekolah::findOrFail($id)->status);

        $this->actingAs($this->sekolah['admin'])
            ->deleteJson("/denah/ruangan/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('ruangan_sekolah', ['id_ruangan' => $id]);
    }

    public function test_non_admin_tidak_boleh_membuat_ruangan(): void
    {
        $this->actingAs($this->sekolah['ketua'])
            ->postJson('/denah/ruangan', $this->payloadRuangan())
            ->assertForbidden();
    }

    public function test_simpan_denah_memangkas_kursi_di_luar_grid(): void
    {
        $denah = $this->buatDenah();

        PosisiDuduk::where('id_denah', $denah->id_denah)
            ->where('id_siswa', $this->sekolah['siswa'][3]->id_siswa)
            ->delete();

        PosisiDuduk::create([
            'id_denah' => $denah->id_denah,
            'id_siswa' => $this->sekolah['siswa'][3]->id_siswa,
            'baris' => 6,
            'kolom' => 4,
        ]);

        $this->actingAs($this->sekolah['ketua'])
            ->putJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas, [
                'jumlah_baris' => 2,
                'jumlah_kolom' => 2,
                'posisi_meja_guru' => 'depan_tengah',
                'posisi_pintu' => 'kanan',
            ])
            ->assertOk();

        $this->assertSame(2, $denah->fresh()->jumlah_baris);
        $this->assertSame(0, PosisiDuduk::where('baris', 6)->count());
    }

    private function buatDenah(): DenahKelas
    {
        $denah = DenahKelas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'jumlah_baris' => 6,
            'jumlah_kolom' => 4,
            'posisi_meja_guru' => 'depan_tengah',
            'posisi_pintu' => 'kanan',
            'berlaku_mulai' => now()->toDateString(),
            'dibuat_oleh' => $this->sekolah['admin']->id_user,
            'is_aktif' => true,
        ]);

        foreach ($this->sekolah['siswa'] as $index => $siswa) {
            PosisiDuduk::create([
                'id_denah' => $denah->id_denah,
                'id_siswa' => $siswa->id_siswa,
                'baris' => intdiv($index, 4) + 1,
                'kolom' => ($index % 4) + 1,
            ]);
        }

        return $denah;
    }

    /** @return array<string, mixed> */
    private function payloadRuangan(): array
    {
        return [
            'kode_ruangan' => 'LAB-UJI',
            'nama_ruangan' => 'Laboratorium Uji',
            'kategori' => 'laboratorium',
            'gedung' => 'Gedung Utama',
            'lantai' => 1,
            'kapasitas' => 30,
            'fasilitas' => ['AC', 'Proyektor'],
            'deskripsi' => 'Ruangan untuk pengujian.',
            'posisi_x' => 1,
            'posisi_y' => 1,
            'lebar_grid' => 4,
            'tinggi_grid' => 3,
            'status' => 'tersedia',
            'jam_buka' => '07:00',
            'jam_tutup' => '15:00',
        ];
    }
}
