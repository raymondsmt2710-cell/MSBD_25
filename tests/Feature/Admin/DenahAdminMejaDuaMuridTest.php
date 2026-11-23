<?php

namespace Tests\Feature\Admin;

use App\Models\DenahKelas;
use App\Models\PosisiDuduk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

/**
 * Halaman admin "Atur kursi" harus memakai aturan yang sama dengan tampilan
 * sisi siswa: satu meja menampung 2 murid (sisi kiri & kanan).
 *
 * Sebelumnya admin memakai satu dropdown per (baris, kolom) tanpa `sisi`.
 * Akibatnya murid kedua di satu meja tertimpa saat form dibaca, dan hilang
 * permanen saat disimpan (hapus-lalu-insert).
 */
class DenahAdminMejaDuaMuridTest extends TestCase
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

    public function test_halaman_atur_kursi_menyediakan_sisi_kiri_dan_kanan_per_meja(): void
    {
        $this->dudukkan($this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri');
        $this->dudukkan($this->sekolah['siswa'][1]->id_siswa, 1, 1, 'kanan');

        $this->actingAs($this->sekolah['admin'])
            ->get(route('admin.denah.edit', $this->denah->id_denah))
            ->assertOk()
            ->assertSee('name="kursi[1][1][kiri]"', false)
            ->assertSee('name="kursi[1][1][kanan]"', false);
    }

    public function test_halaman_atur_kursi_menampilkan_murid_di_kedua_sisi_meja(): void
    {
        $kiri = $this->sekolah['siswa'][0];
        $kanan = $this->sekolah['siswa'][1];

        $this->dudukkan($kiri->id_siswa, 1, 1, 'kiri');
        $this->dudukkan($kanan->id_siswa, 1, 1, 'kanan');

        $html = $this->actingAs($this->sekolah['admin'])
            ->get(route('admin.denah.edit', $this->denah->id_denah))
            ->assertOk()
            ->getContent();

        // Kedua murid harus tampil sebagai pilihan yang terpilih, bukan saling menimpa.
        $this->assertMatchesRegularExpression(
            '/value="'.$kiri->id_siswa.'"[^>]*selected/',
            $html,
            'Murid sisi kiri tidak tampil terpilih di halaman admin.',
        );
        $this->assertMatchesRegularExpression(
            '/value="'.$kanan->id_siswa.'"[^>]*selected/',
            $html,
            'Murid sisi kanan tidak tampil terpilih di halaman admin.',
        );
    }

    public function test_menyimpan_posisi_tidak_menghapus_murid_kedua_di_meja_yang_sama(): void
    {
        $kiri = $this->dudukkan($this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri');
        $kanan = $this->dudukkan($this->sekolah['siswa'][1]->id_siswa, 1, 1, 'kanan');

        $this->actingAs($this->sekolah['admin'])
            ->put(route('admin.denah.posisi', $this->denah->id_denah), [
                'kursi' => [1 => [1 => [
                    'kiri' => $kiri->id_siswa,
                    'kanan' => $kanan->id_siswa,
                ]]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(
            2,
            PosisiDuduk::where('id_denah', $this->denah->id_denah)->count(),
            'Menyimpan posisi tidak boleh menghilangkan murid.',
        );

        $this->assertDatabaseHas('posisi_duduk', [
            'id_denah' => $this->denah->id_denah,
            'id_siswa' => $kiri->id_siswa,
            'baris' => 1, 'kolom' => 1, 'sisi' => 'kiri',
        ]);
        $this->assertDatabaseHas('posisi_duduk', [
            'id_denah' => $this->denah->id_denah,
            'id_siswa' => $kanan->id_siswa,
            'baris' => 1, 'kolom' => 1, 'sisi' => 'kanan',
        ]);
    }

    public function test_buat_denah_baru_mengisi_dua_murid_per_meja(): void
    {
        // 4 murid, 2 meja (1 baris x 2 kolom) x 2 sisi = 4 kursi -> semua kebagian.
        $this->actingAs($this->sekolah['admin'])
            ->post(route('admin.denah.store', $this->sekolah['kelas']->id_kelas), [
                'jumlah_baris' => 1,
                'jumlah_kolom' => 2,
                'posisi_meja_guru' => 'depan_tengah',
                'berlaku_mulai' => now()->toDateString(),
                'isi' => 'absen',
                'aktifkan' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $baru = DenahKelas::where('id_kelas', $this->sekolah['kelas']->id_kelas)
            ->where('is_aktif', true)->latest('id_denah')->firstOrFail();

        $kursi = PosisiDuduk::where('id_denah', $baru->id_denah)->get();
        $this->assertSame(4, $kursi->count());

        $perMeja = $kursi->groupBy(fn ($p) => $p->baris.'-'.$p->kolom);
        $this->assertCount(2, $perMeja, 'Dua meja harus terisi.');

        foreach ($perMeja as $rows) {
            $this->assertCount(2, $rows, 'Setiap meja harus berisi 2 murid.');
            $this->assertEqualsCanonicalizing(['kiri', 'kanan'], $rows->pluck('sisi')->all());
        }
    }

    public function test_salin_denah_menyalin_sisi_kiri_dan_kanan(): void
    {
        $this->dudukkan($this->sekolah['siswa'][0]->id_siswa, 1, 1, 'kiri');
        $this->dudukkan($this->sekolah['siswa'][1]->id_siswa, 1, 1, 'kanan');

        $this->actingAs($this->sekolah['admin'])
            ->post(route('admin.denah.store', $this->sekolah['kelas']->id_kelas), [
                'jumlah_baris' => 2,
                'jumlah_kolom' => 2,
                'posisi_meja_guru' => 'depan_tengah',
                'berlaku_mulai' => now()->toDateString(),
                'isi' => 'salin',
                'aktifkan' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $baru = DenahKelas::where('id_kelas', $this->sekolah['kelas']->id_kelas)
            ->where('is_aktif', true)->latest('id_denah')->firstOrFail();

        $this->assertSame(2, PosisiDuduk::where('id_denah', $baru->id_denah)->count());
        $this->assertSame(
            1,
            PosisiDuduk::where('id_denah', $baru->id_denah)->where('sisi', 'kanan')->count(),
            'Sisi kanan harus ikut tersalin.',
        );
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
