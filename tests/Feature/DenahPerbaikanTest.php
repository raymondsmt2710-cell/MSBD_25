<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DenahKelas;
use App\Models\OrangTua;
use App\Models\PosisiDuduk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatSekolah;
use Tests\TestCase;

class DenahPerbaikanTest extends TestCase
{
    use MembuatSekolah, RefreshDatabase;


    private array $sekolah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->sekolah = $this->buatSekolah();
    }



    public function test_orang_tua_hanya_menerima_kursi_anaknya(): void
    {
        $denah = $this->buatDenahDenganEmpatMurid();
        $anak = $this->sekolah['siswa'][0];
        $orangTua = $this->orangTuaDari($anak->id_siswa);

        $respons = $this->actingAs($orangTua)
            ->getJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/data')
            ->assertOk();

        $payload = $respons->json();
        $idSiswaTerlihat = $this->kumpulkanIdSiswa($payload['kursi']);

        $this->assertSame([$anak->id_siswa], $idSiswaTerlihat);


        $idsPanel = array_column($payload['siswa'], 'id_siswa');
        $this->assertSame([$anak->id_siswa], $idsPanel);

        $this->assertSame($denah->id_denah, $payload['denah']['id_denah']);
    }

    public function test_orang_tua_tidak_melihat_nama_teman_sekelas(): void
    {
        $this->buatDenahDenganEmpatMurid();
        $anak = $this->sekolah['siswa'][0];
        $orangTua = $this->orangTuaDari($anak->id_siswa);

        $respons = $this->actingAs($orangTua)
            ->get('/denah')
            ->assertOk();

        $respons->assertSee($anak->nama_siswa);

        foreach ([1, 2, 3] as $index) {
            $respons->assertDontSee($this->sekolah['siswa'][$index]->nama_siswa);
        }
    }

    public function test_ketua_kelas_tetap_melihat_semua_kursi(): void
    {
        $this->buatDenahDenganEmpatMurid();

        $payload = $this->actingAs($this->sekolah['ketua'])
            ->getJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas.'/data')
            ->assertOk()
            ->json();

        $this->assertCount(4, $this->kumpulkanIdSiswa($payload['kursi']));
        $this->assertCount(4, $payload['siswa']);
    }



    public function test_memperkecil_grid_ditolak_bila_ada_kursi_di_luar_grid(): void
    {
        $denah = $this->buatDenahDenganEmpatMurid();

        $this->actingAs($this->sekolah['admin'])
            ->putJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas, [
                'jumlah_baris' => 1,
                'jumlah_kolom' => 2,
                'posisi_meja_guru' => 'depan_tengah',
                'posisi_pintu' => 'kanan',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $pesan) => str_contains($pesan, 'murid berada di luar ukuran baru'));


        $this->assertSame(4, PosisiDuduk::where('id_denah', $denah->id_denah)->count());
        $this->assertSame(2, $denah->fresh()->jumlah_baris);
    }

    public function test_memperkecil_grid_berhasil_bila_tidak_ada_kursi_terdampak(): void
    {
        $denah = $this->buatDenahDenganEmpatMurid();


        $this->actingAs($this->sekolah['admin'])
            ->putJson('/denah/kelas/'.$this->sekolah['kelas']->id_kelas, [
                'jumlah_baris' => 2,
                'jumlah_kolom' => 2,
                'posisi_meja_guru' => 'depan_tengah',
                'posisi_pintu' => 'kanan',
            ])
            ->assertOk();

        $this->assertSame(4, PosisiDuduk::where('id_denah', $denah->id_denah)->count());
    }



    private function buatDenahDenganEmpatMurid(): DenahKelas
    {
        $denah = DenahKelas::create([
            'id_kelas' => $this->sekolah['kelas']->id_kelas,
            'jumlah_baris' => 2,
            'jumlah_kolom' => 2,
            'posisi_meja_guru' => 'depan_tengah',
            'posisi_pintu' => 'kanan',
            'berlaku_mulai' => now()->toDateString(),
            'dibuat_oleh' => $this->sekolah['ketua']->id_user,
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

    private function orangTuaDari(int $idSiswa): User
    {
        $user = User::create([
            'username' => 'ortu_'.$idSiswa,
            'password_hash' => 'password',
            'role' => Role::OrangTua,
            'is_active' => true,
        ]);

        $orangTua = OrangTua::create([
            'id_user' => $user->id_user,
            'nama' => 'Wali Dari '.$idSiswa,
            'pekerjaan' => 'Karyawan',
            'no_hp' => '08120000000',
        ]);

        $orangTua->siswa()->attach($idSiswa, ['hubungan' => 'wali', 'is_kontak_utama' => true]);

        return $user->fresh();
    }


    private function kumpulkanIdSiswa(array $kursi): array
    {
        $ids = [];

        foreach ($kursi as $meja) {
            foreach (['kiri', 'kanan'] as $sisi) {
                $id = $meja['murid'][$sisi]['siswa']['id_siswa'] ?? null;
                if ($id) {
                    $ids[] = (int) $id;
                }
            }
        }

        sort($ids);

        return $ids;
    }
}
