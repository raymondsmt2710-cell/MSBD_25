<?php

namespace Tests\Feature;

use App\Enums\JenisKas;
use App\Enums\KategoriKas;
use App\Enums\StatusIuran;
use App\Models\IuranKas;
use App\Models\TransaksiKas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

class KasTest extends TestCase
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

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/kas')->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_halaman_kas(): void
    {
        $this->actingAs($this->sekolah['admin'])
            ->get('/kas')
            ->assertOk()
            ->assertSee('Kas Kelas');
    }

    public function test_wadah_tabel_transaksi_bisa_digeser_bukan_memotong(): void
    {
        // overflow-hidden memotong kolom terakhir tabel transaksi sehingga datanya
        // tidak bisa diakses sama sekali di layar sempit. Harus bisa digeser.
        $html = $this->actingAs($this->sekolah['admin'])
            ->get('/kas')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            'class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100"',
            $html,
            'Wadah tabel transaksi harus bisa digeser, bukan memotong isi.'
        );

        $this->assertStringContainsString(
            'class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100"',
            $html,
            'Wadah tabel transaksi harus memakai overflow-x-auto.'
        );
    }

    public function test_siswa_biasa_hanya_bisa_melihat(): void
    {
        $this->actingAs($this->sekolah['anggota'])
            ->get('/kas')
            ->assertOk();

        $this->actingAs($this->sekolah['anggota'])
            ->postJson('/kas/transaksi', $this->payloadTransaksi())
            ->assertForbidden();
    }

    public function test_ketua_kelas_tidak_boleh_mencatat_transaksi(): void
    {
        $this->actingAs($this->sekolah['ketua'])
            ->postJson('/kas/transaksi', $this->payloadTransaksi())
            ->assertForbidden();
    }

    public function test_bendahara_dapat_mencatat_transaksi(): void
    {
        $response = $this->actingAs($this->sekolah['bendahara'])
            ->postJson('/kas/transaksi', $this->payloadTransaksi());

        $response->assertCreated();

        $this->assertDatabaseHas('transaksi_kas', [
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'jenis' => JenisKas::Pemasukan->value,
            'nominal' => 25000,
        ]);
    }

    public function test_bendahara_tidak_boleh_menulis_ke_kelas_lain(): void
    {
        $payload = $this->payloadTransaksi();
        $payload['id_kelas'] = $this->sekolah['kelasLain']->id_kelas;
        unset($payload['id_siswa']);

        $this->actingAs($this->sekolah['bendahara'])
            ->postJson('/kas/transaksi', $payload)
            ->assertForbidden();

        $this->assertDatabaseCount('transaksi_kas', 0);
    }

    public function test_siswa_hanya_melihat_baris_iuran_miliknya(): void
    {
        $periode = now()->format('Y-m');

        foreach ($this->sekolah['siswa'] as $siswa) {
            IuranKas::create([
                'id_kelas' => $this->sekolah['kelas']->id_kelas,
                'id_siswa' => $siswa->id_siswa,
                'periode' => $periode,
                'nominal' => 20000,
                'status' => StatusIuran::BelumBayar,
            ]);
        }

        $milikSendiri = $this->sekolah['siswa'][2]->id_siswa;

        $response = $this->actingAs($this->sekolah['anggota'])
            ->getJson('/kas/data?tahun='.now()->year);

        $response->assertOk();

        $baris = $response->json('matriks.siswa');

        $this->assertCount(1, $baris);
        $this->assertSame($milikSendiri, $baris[0]['id_siswa']);
    }

    public function test_admin_melihat_semua_baris_matriks(): void
    {
        $response = $this->actingAs($this->sekolah['admin'])
            ->getJson('/kas/data?tahun='.now()->year);

        $response->assertOk();
        $this->assertCount(4, $response->json('matriks.siswa'));
    }

    public function test_verifikasi_iuran_hanya_membuat_satu_transaksi(): void
    {
        $iuran = $this->iuranBelumBayar();

        $this->actingAs($this->sekolah['bendahara'])
            ->patchJson("/kas/iuran/{$iuran->id_iuran}/verifikasi", ['status' => 'terverifikasi'])
            ->assertOk();

        $this->assertDatabaseCount('transaksi_kas', 1);

        $iuran->refresh();
        $this->assertSame(StatusIuran::Terverifikasi, $iuran->status);
        $this->assertNotNull($iuran->id_transaksi);

        $this->actingAs($this->sekolah['bendahara'])
            ->patchJson("/kas/iuran/{$iuran->id_iuran}/verifikasi", ['status' => 'terverifikasi'])
            ->assertStatus(422);

        $this->assertDatabaseCount('transaksi_kas', 1);
    }

    public function test_transaksi_hasil_verifikasi_tidak_dapat_diedit(): void
    {
        $iuran = $this->iuranBelumBayar();

        $this->actingAs($this->sekolah['bendahara'])
            ->patchJson("/kas/iuran/{$iuran->id_iuran}/verifikasi", ['status' => 'terverifikasi'])
            ->assertOk();

        $transaksi = TransaksiKas::firstOrFail();

        $this->actingAs($this->sekolah['bendahara'])
            ->putJson("/kas/transaksi/{$transaksi->id_transaksi}", ['keterangan' => 'Diubah'])
            ->assertStatus(422);
    }

    public function test_pembatalan_transaksi_mengembalikan_status_iuran(): void
    {
        $iuran = $this->iuranBelumBayar();

        $this->actingAs($this->sekolah['bendahara'])
            ->patchJson("/kas/iuran/{$iuran->id_iuran}/verifikasi", ['status' => 'terverifikasi'])
            ->assertOk();

        $transaksi = TransaksiKas::firstOrFail();

        $this->actingAs($this->sekolah['bendahara'])
            ->deleteJson("/kas/transaksi/{$transaksi->id_transaksi}", ['alasan_batal' => 'Salah input'])
            ->assertOk();

        $this->assertTrue($transaksi->fresh()->is_batal);

        $iuran->refresh();
        $this->assertSame(StatusIuran::BelumBayar, $iuran->status);
        $this->assertNull($iuran->id_transaksi);
    }

    public function test_ringkasan_bulan_ini_menghitung_transaksi_tanggal_satu(): void
    {
        $tanggal = now()->startOfMonth()->toDateString();

        TransaksiKas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'jenis' => JenisKas::Pemasukan->value,
            'kategori' => KategoriKas::IuranRutin->value,
            'nominal' => 50000,
            'tanggal' => $tanggal,
            'keterangan' => 'Masuk awal bulan',
            'dicatat_oleh' => $this->sekolah['admin']->id_user,
        ]);

        TransaksiKas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'jenis' => JenisKas::Pengeluaran->value,
            'kategori' => KategoriKas::Konsumsi->value,
            'nominal' => 20000,
            'tanggal' => $tanggal,
            'keterangan' => 'Keluar awal bulan',
            'dicatat_oleh' => $this->sekolah['admin']->id_user,
        ]);

        $response = $this->actingAs($this->sekolah['admin'])
            ->getJson('/kas/data?tahun='.now()->year);

        $response->assertOk();

        $this->assertSame(50000.0, (float) $response->json('ringkasan.pemasukan_bulan_ini'));
        $this->assertSame(20000.0, (float) $response->json('ringkasan.pengeluaran_bulan_ini'));
        $this->assertSame(30000.0, (float) $response->json('ringkasan.saldo'));
    }

    public function test_export_csv_dapat_diunduh(): void
    {
        $response = $this->actingAs($this->sekolah['bendahara'])
            ->get('/kas/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }

    public function test_halaman_cetak_dapat_dibuka(): void
    {
        $this->actingAs($this->sekolah['bendahara'])
            ->get('/kas/cetak')
            ->assertOk()
            ->assertSee('Laporan Keuangan Kas Kelas');
    }

    /** @return array<string, mixed> */
    private function payloadTransaksi(): array
    {
        return [
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'tanggal' => now()->toDateString(),
            'jenis' => JenisKas::Pemasukan->value,
            'kategori' => KategoriKas::IuranRutin->value,
            'nominal' => 25000,
            'keterangan' => 'Iuran kas uji',
            'id_siswa' => $this->sekolah['siswa'][0]->id_siswa,
        ];
    }

    private function iuranBelumBayar(): IuranKas
    {
        return IuranKas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'id_siswa' => $this->sekolah['siswa'][0]->id_siswa,
            'periode' => now()->format('Y-m'),
            'nominal' => 20000,
            'status' => StatusIuran::BelumBayar,
        ]);
    }
}
