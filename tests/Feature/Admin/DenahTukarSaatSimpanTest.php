<?php

namespace Tests\Feature\Admin;

use App\Models\DenahKelas;
use App\Models\PosisiDuduk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

/**
 * Kasus sulit pada penyimpanan posisi: menukar tempat dua murid sekaligus.
 * Karena unique (id_denah, baris, kolom, sisi), memindahkan A ke kursi B
 * sebelum B dipindahkan akan menabrak constraint.
 */
class DenahTukarSaatSimpanTest extends TestCase
{
    use MembuatSekolah, RefreshDatabase;

    /** @var array<string, mixed> */
    private array $sekolah;

    private DenahKelas $denah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->sekolah = $this->buatSekolah();

        $this->denah = DenahKelas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'jumlah_baris' => 2,
            'jumlah_kolom' => 2,
            'posisi_meja_guru' => 'depan_tengah',
            'posisi_pintu' => 'kanan',
            'berlaku_mulai' => now()->toDateString(),
            'dibuat_oleh' => $this->sekolah['admin']->id_user,
            'is_aktif' => true,
        ]);
    }

    public function test_menukar_dua_murid_dalam_satu_kali_simpan_berhasil(): void
    {
        $a = $this->dudukkan($this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri');
        $b = $this->dudukkan($this->sekolah['siswa'][1]->id_siswa, 1, 2, 'kiri');

        $idA = $a->id_posisi;
        $idB = $b->id_posisi;

        // A ke kursi B, B ke kursi A: keduanya bertukar tempat.
        $this->actingAs($this->sekolah['admin'])
            ->put(route('admin.denah.posisi', $this->denah->id_denah), [
                'kursi' => [1 => [
                    1 => ['kiri' => $b->id_siswa, 'kanan' => null],
                    2 => ['kiri' => $a->id_siswa, 'kanan' => null],
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // id tetap, posisi bertukar.
        $this->assertDatabaseHas('posisi_duduk', [
            'id_posisi' => $idA, 'id_siswa' => $a->id_siswa, 'baris' => 1, 'kolom' => 2, 'sisi' => 'kiri',
        ]);
        $this->assertDatabaseHas('posisi_duduk', [
            'id_posisi' => $idB, 'id_siswa' => $b->id_siswa, 'baris' => 1, 'kolom' => 1, 'sisi' => 'kiri',
        ]);
        $this->assertSame(2, PosisiDuduk::where('id_denah', $this->denah->id_denah)->count());
    }

    public function test_menukar_sisi_kiri_dan_kanan_pada_meja_yang_sama(): void
    {
        $a = $this->dudukkan($this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri');
        $b = $this->dudukkan($this->sekolah['siswa'][1]->id_siswa, 1, 1, 'kanan');

        $idA = $a->id_posisi;
        $idB = $b->id_posisi;

        $this->actingAs($this->sekolah['admin'])
            ->put(route('admin.denah.posisi', $this->denah->id_denah), [
                'kursi' => [1 => [1 => [
                    'kiri' => $b->id_siswa,
                    'kanan' => $a->id_siswa,
                ]]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('posisi_duduk', [
            'id_posisi' => $idA, 'id_siswa' => $a->id_siswa, 'sisi' => 'kanan',
        ]);
        $this->assertDatabaseHas('posisi_duduk', [
            'id_posisi' => $idB, 'id_siswa' => $b->id_siswa, 'sisi' => 'kiri',
        ]);
    }

    private function dudukkan(int $idSiswa, int $baris, int $kolom, string $sisi): PosisiDuduk
    {
        return PosisiDuduk::create([
            'id_denah' => $this->denah->id_denah,
            'id_siswa' => $idSiswa,
            'baris' => $baris,
            'kolom' => $kolom,
            'sisi' => $sisi,
        ]);
    }
}
