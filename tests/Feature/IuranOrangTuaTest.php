<?php

namespace Tests\Feature;

use App\Enums\JenisKas;
use App\Enums\KategoriKas;
use App\Enums\Role;
use App\Enums\StatusIuran;
use App\Models\IuranKas;
use App\Models\OrangTua;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

/**
 * Orang tua melaporkan pembayaran iuran ANAKNYA SENDIRI dengan mengunggah
 * bukti, dan hanya melihat kas anaknya.
 *
 * Prinsip yang dijaga:
 *  - Orang tua tidak pernah mendapat hak kelola (kas.kelola tetap false).
 *  - Laporan hanya memindahkan status ke "menunggu"; uang baru dihitung
 *    setelah bendahara memverifikasi.
 *  - Orang tua tidak bisa melaporkan untuk anak orang lain.
 */
class IuranOrangTuaTest extends TestCase
{
    use MembuatSekolah, RefreshDatabase;

    /** @var array<string, mixed> */
    private array $sekolah;

    private User $orangTua;

    private User $orangTuaLain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        $this->sekolah = $this->buatSekolah();

        // Anak pertama: Ahmad Fauzi. Anak ketiga: Budi Santoso.
        $this->orangTua = $this->buatOrangTua('ortu_ahmad', $this->sekolah['siswa'][0]->id_siswa);
        $this->orangTuaLain = $this->buatOrangTua('ortu_budi', $this->sekolah['siswa'][2]->id_siswa);
    }

    // ---- Kebocoran yang diperbaiki: panel anggota ----

    public function test_orang_tua_hanya_menerima_data_anaknya_di_panel_anggota(): void
    {
        $payload = $this->actingAs($this->orangTua)
            ->getJson('/kas/data?id_kelas='.$this->sekolah['kelas']->id_kelas)
            ->assertOk()
            ->json();

        $nama = array_column($payload['anggota'], 'nama');

        $this->assertSame([$this->sekolah['siswa'][0]->nama_siswa], $nama);
    }

    public function test_orang_tua_tidak_mendapat_hak_kelola(): void
    {
        $payload = $this->actingAs($this->orangTua)
            ->getJson('/kas/data?id_kelas='.$this->sekolah['kelas']->id_kelas)
            ->assertOk()
            ->json();

        $this->assertFalse($payload['bisaKelola']);
    }

    // ---- Lapor pembayaran ----

    public function test_orang_tua_dapat_melaporkan_pembayaran_anaknya(): void
    {
        $iuran = $this->iuran($this->sekolah['siswa'][0]->id_siswa, StatusIuran::BelumBayar);

        $this->actingAs($this->orangTua)
            ->postJson('/kas/iuran/'.$iuran->id_iuran.'/lapor', [
                'bukti' => UploadedFile::fake()->image('transfer.jpg'),
                'catatan' => 'Transfer BRI',
            ])
            ->assertOk()
            ->assertJsonPath('iuran.status', StatusIuran::Menunggu->value);

        $iuran->refresh();

        $this->assertSame(StatusIuran::Menunggu, $iuran->status);
        $this->assertNotNull($iuran->bukti_url);
        Storage::disk('public')->assertExists($iuran->bukti_url);

        // Uang belum masuk: belum ada transaksi kas baru.
        $this->assertSame(0, TransaksiKas::count());
    }

    public function test_laporan_hanya_menunggu_dan_belum_terverifikasi(): void
    {
        $iuran = $this->iuran($this->sekolah['siswa'][0]->id_siswa, StatusIuran::BelumBayar);

        $this->actingAs($this->orangTua)
            ->postJson('/kas/iuran/'.$iuran->id_iuran.'/lapor', [
                'bukti' => UploadedFile::fake()->image('bukti.png'),
            ])
            ->assertOk();

        $this->assertNotSame(StatusIuran::Terverifikasi, $iuran->fresh()->status);
        $this->assertNull($iuran->fresh()->id_transaksi);
    }

    public function test_orang_tua_tidak_bisa_melaporkan_untuk_anak_orang_lain(): void
    {
        $iuranAnakOrangLain = $this->iuran($this->sekolah['siswa'][2]->id_siswa, StatusIuran::BelumBayar);

        $this->actingAs($this->orangTua)
            ->postJson('/kas/iuran/'.$iuranAnakOrangLain->id_iuran.'/lapor', [
                'bukti' => UploadedFile::fake()->image('transfer.jpg'),
            ])
            ->assertForbidden();

        $this->assertSame(StatusIuran::BelumBayar, $iuranAnakOrangLain->fresh()->status);
        $this->assertNull($iuranAnakOrangLain->fresh()->bukti_url);
    }

    public function test_orang_tua_tidak_bisa_mengubah_status_menjadi_terverifikasi(): void
    {
        $iuran = $this->iuran($this->sekolah['siswa'][0]->id_siswa, StatusIuran::BelumBayar);

        $this->actingAs($this->orangTua)
            ->postJson('/kas/iuran/'.$iuran->id_iuran.'/lapor', [
                'bukti' => UploadedFile::fake()->image('transfer.jpg'),
                'status' => StatusIuran::Terverifikasi->value,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertSame(StatusIuran::BelumBayar, $iuran->fresh()->status);
    }

    public function test_laporan_tetap_butuh_bukti(): void
    {
        $iuran = $this->iuran($this->sekolah['siswa'][0]->id_siswa, StatusIuran::BelumBayar);

        $this->actingAs($this->orangTua)
            ->postJson('/kas/iuran/'.$iuran->id_iuran.'/lapor', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('bukti');
    }

    public function test_tidak_bisa_melaporkan_iuran_yang_sudah_terverifikasi(): void
    {
        $iuran = $this->iuran($this->sekolah['siswa'][0]->id_siswa, StatusIuran::Terverifikasi);

        $this->actingAs($this->orangTua)
            ->postJson('/kas/iuran/'.$iuran->id_iuran.'/lapor', [
                'bukti' => UploadedFile::fake()->image('transfer.jpg'),
            ])
            ->assertStatus(422);
    }

    public function test_siswa_biasa_tidak_boleh_melaporkan_via_jalur_orang_tua(): void
    {
        $iuran = $this->iuran($this->sekolah['siswa'][0]->id_siswa, StatusIuran::BelumBayar);

        $this->actingAs($this->sekolah['anggota'])
            ->postJson('/kas/iuran/'.$iuran->id_iuran.'/lapor', [
                'bukti' => UploadedFile::fake()->image('transfer.jpg'),
            ])
            ->assertForbidden();
    }

    // ---- Bendahara memverifikasi laporan ----

    public function test_bendahara_dapat_memverifikasi_laporan_orang_tua(): void
    {
        $iuran = $this->iuran($this->sekolah['siswa'][0]->id_siswa, StatusIuran::BelumBayar);

        $this->actingAs($this->orangTua)
            ->postJson('/kas/iuran/'.$iuran->id_iuran.'/lapor', [
                'bukti' => UploadedFile::fake()->image('transfer.jpg'),
            ])
            ->assertOk();

        $this->actingAs($this->sekolah['bendahara'])
            ->patchJson('/kas/iuran/'.$iuran->id_iuran.'/verifikasi', ['status' => 'terverifikasi'])
            ->assertOk();

        $iuran->refresh();

        $this->assertSame(StatusIuran::Terverifikasi, $iuran->status);
        $this->assertNotNull($iuran->id_transaksi);
        // Baru sekarang uang dihitung sebagai pemasukan.
        $this->assertSame(1, TransaksiKas::count());
    }

    public function test_matriks_mengirim_url_bukti_agar_bendahara_bisa_memeriksa(): void
    {
        $iuran = $this->iuran($this->sekolah['siswa'][0]->id_siswa, StatusIuran::BelumBayar);

        $this->actingAs($this->orangTua)
            ->postJson('/kas/iuran/'.$iuran->id_iuran.'/lapor', [
                'bukti' => UploadedFile::fake()->image('transfer.jpg'),
            ])
            ->assertOk();

        $payload = $this->actingAs($this->sekolah['bendahara'])
            ->getJson('/kas/data?id_kelas='.$this->sekolah['kelas']->id_kelas)
            ->assertOk()
            ->json();

        $sel = $payload['matriks']['siswa'][0]['iuran'][now()->format('m')];

        $this->assertNotNull($sel['bukti_tampil']);
        $this->assertStringContainsString('/storage/', $sel['bukti_tampil']);
    }

    // ---- helper ----

    private function iuran(int $idSiswa, StatusIuran $status): IuranKas
    {
        return IuranKas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'id_siswa' => $idSiswa,
            'periode' => now()->format('Y-m'),
            'nominal' => 20000,
            'status' => $status,
        ]);
    }

    private function buatOrangTua(string $username, int $idSiswa): User
    {
        $user = User::create([
            'username' => $username,
            'password_hash' => 'password',
            'role' => Role::OrangTua,
            'is_active' => true,
        ]);

        $orangTua = OrangTua::create([
            'id_user' => $user->id_user,
            'nama' => 'Wali '.$username,
        ]);

        $orangTua->siswa()->attach($idSiswa, ['hubungan' => 'wali', 'is_kontak_utama' => true]);

        return $user->fresh();
    }
}
