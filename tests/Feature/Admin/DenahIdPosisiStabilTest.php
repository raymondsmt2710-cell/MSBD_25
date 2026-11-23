<?php

namespace Tests\Feature\Admin;

use App\Models\DenahKelas;
use App\Models\PosisiDuduk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

/**
 * Menyimpan posisi dari halaman admin memakai pola hapus-lalu-insert, sehingga
 * `id_posisi` seluruh baris berubah setiap kali disimpan. Dua jalur lain
 * (pindah & tukar di sisi siswa) memakai id_posisi sebagai rujukan, jadi
 * menyimpan dari admin membuat rujukan itu menunjuk baris yang sudah tidak ada.
 */
class DenahIdPosisiStabilTest extends TestCase
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

    public function test_menyimpan_posisi_tidak_mengubah_id_posisi_yang_sudah_ada(): void
    {
        $a = $this->dudukkan($this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri');
        $b = $this->dudukkan($this->sekolah['siswa'][1]->id_siswa, 1, 1, 'kanan');

        $idA = $a->id_posisi;
        $idB = $b->id_posisi;

        // Simpan ulang susunan yang sama, hanya memindahkan siswa B ke meja lain.
        $this->actingAs($this->sekolah['admin'])
            ->put(route('admin.denah.posisi', $this->denah->id_denah), [
                'kursi' => [1 => [
                    1 => ['kiri' => $a->id_siswa, 'kanan' => null],
                    2 => ['kiri' => $b->id_siswa, 'kanan' => null],
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('posisi_duduk', [
            'id_posisi' => $idA, 'id_siswa' => $a->id_siswa,
            'baris' => 1, 'kolom' => 1, 'sisi' => 'kiri',
        ]);
        $this->assertDatabaseHas('posisi_duduk', [
            'id_posisi' => $idB, 'id_siswa' => $b->id_siswa,
            'baris' => 1, 'kolom' => 2, 'sisi' => 'kiri',
        ]);
    }

    public function test_rujukan_id_posisi_dari_sisi_siswa_tetap_sah_setelah_admin_menyimpan(): void
    {
        $a = $this->dudukkan($this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri');
        $b = $this->dudukkan($this->sekolah['siswa'][1]->id_siswa, 1, 1, 'kanan');

        // Klien sisi siswa memegang id_posisi ini (dari payload /denah/kelas/{id}/data).
        $idA = $a->id_posisi;
        $idB = $b->id_posisi;

        $this->actingAs($this->sekolah['admin'])
            ->put(route('admin.denah.posisi', $this->denah->id_denah), [
                'kursi' => [1 => [1 => ['kiri' => $a->id_siswa, 'kanan' => $b->id_siswa]]],
            ])
            ->assertRedirect();

        // Tukar posisi memakai id_posisi lama: harus tetap berhasil.
        $this->actingAs($this->sekolah['admin'])
            ->postJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/swap', [
                'posisi_a' => $idA,
                'posisi_b' => $idB,
            ])
            ->assertOk();

        $this->assertDatabaseHas('posisi_duduk', [
            'id_posisi' => $idA, 'baris' => 1, 'kolom' => 1, 'sisi' => 'kanan',
        ]);
        $this->assertDatabaseHas('posisi_duduk', [
            'id_posisi' => $idB, 'baris' => 1, 'kolom' => 1, 'sisi' => 'kiri',
        ]);
    }

    public function test_menyimpan_posisi_menghapus_kursi_yang_dikosongkan(): void
    {
        $a = $this->dudukkan($this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri');
        $b = $this->dudukkan($this->sekolah['siswa'][1]->id_siswa, 1, 2, 'kiri');

        $this->actingAs($this->sekolah['admin'])
            ->put(route('admin.denah.posisi', $this->denah->id_denah), [
                'kursi' => [1 => [1 => ['kiri' => $a->id_siswa, 'kanan' => null]]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('posisi_duduk', ['id_siswa' => $b->id_siswa]);
        $this->assertSame(1, PosisiDuduk::where('id_denah', $this->denah->id_denah)->count());
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
