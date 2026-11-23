<?php

namespace Tests\Feature;

use App\Enums\JenisKas;
use App\Enums\KategoriKas;
use App\Models\TransaksiKas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

/**
 * Regresi bug #1: tombol "Batal" di halaman kas mengirim POST + FormData
 * dengan `_method=DELETE` tanpa header Content-Type. PHP tidak mengurai body
 * sehingga method override tidak terbaca dan request jatuh ke route
 * POST /kas/transaksi (store), gagal validasi, transaksi tidak pernah batal.
 *
 * Perbaikannya: kirim DELETE sungguhan dengan body JSON.
 */
class KasBatalTransaksiTest extends TestCase
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

    public function test_bendahara_dapat_membatalkan_transaksi_lewat_http_delete(): void
    {
        $transaksi = $this->transaksi();

        $this->actingAs($this->sekolah['bendahara'])
            ->deleteJson('/kas/transaksi/'.$transaksi->id_transaksi, [
                'alasan_batal' => 'Salah catat nominal',
            ])
            ->assertOk();

        $transaksi->refresh();

        $this->assertTrue($transaksi->is_batal);
        $this->assertSame('Salah catat nominal', $transaksi->alasan_batal);
        $this->assertSame($this->sekolah['bendahara']->id_user, $transaksi->dibatalkan_oleh);
        $this->assertNotNull($transaksi->dibatalkan_pada);
    }

    public function test_pembatalan_tanpa_alasan_ditolak(): void
    {
        $transaksi = $this->transaksi();

        $this->actingAs($this->sekolah['bendahara'])
            ->deleteJson('/kas/transaksi/'.$transaksi->id_transaksi)
            ->assertStatus(422)
            ->assertJsonValidationErrors('alasan_batal');

        $this->assertFalse($transaksi->fresh()->is_batal);
    }

    public function test_transaksi_yang_sudah_batal_tidak_dapat_dibatalkan_lagi(): void
    {
        $transaksi = $this->transaksi();

        $this->actingAs($this->sekolah['bendahara'])
            ->deleteJson('/kas/transaksi/'.$transaksi->id_transaksi, ['alasan_batal' => 'Pertama'])
            ->assertOk();

        $this->actingAs($this->sekolah['bendahara'])
            ->deleteJson('/kas/transaksi/'.$transaksi->id_transaksi, ['alasan_batal' => 'Kedua'])
            ->assertStatus(422);

        $this->assertSame('Pertama', $transaksi->fresh()->alasan_batal);
    }

    private function transaksi(): TransaksiKas
    {
        return TransaksiKas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'jenis' => JenisKas::Pengeluaran->value,
            'kategori' => KategoriKas::Konsumsi->value,
            'nominal' => 25000,
            'tanggal' => now()->toDateString(),
            'keterangan' => 'Beli air minum rapat',
            'dicatat_oleh' => $this->sekolah['bendahara']->id_user,
        ]);
    }
}
