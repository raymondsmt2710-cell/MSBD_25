<?php

namespace Tests\Feature\Organisasi;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Halaman LIHAT: siswa, guru, admin boleh; orang tua tidak.
 */
class PerangkatHalamanTest extends TestCase
{
    use DatabaseTruncation;
    use OrganisasiTestSupport;

    public function test_siswa_bisa_membuka_daftar_perangkat(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('perangkat.index'))
            ->assertOk()
            ->assertSee($d['id_kelas'] ? 'X Uji' : 'x');
    }

    public function test_siswa_bisa_melihat_pengurus_kelas(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('perangkat.kelas', $d['id_kelas']))
            ->assertOk()
            ->assertSee('Susunan Pengurus')
            ->assertSee('Ketua Kelas');
    }

    public function test_siswa_bisa_melihat_pengurus_organisasi(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('perangkat.organisasi', $d['id_organisasi']))
            ->assertOk()
            ->assertSee('Susunan Pengurus')
            ->assertSee('Ketua OSIS');
    }

    public function test_jabatan_kosong_tampil_belum_diisi(): void
    {
        $d = $this->seedPerangkat();

        // Jabatan kelas ke-3 dan ke-4 belum diisi pada data uji.
        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('perangkat.kelas', $d['id_kelas']))
            ->assertOk()
            ->assertSee('belum diisi');
    }

    public function test_semua_jabatan_tetap_tampil_walau_pengurus_kosong(): void
    {
        $d = $this->seedPerangkat();

        // Kosongkan seluruh pengurus kelas.
        DB::connection('mysql')->table('pengurus_kelas')->where('id_kelas', $d['id_kelas'])->delete();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('perangkat.kelas', $d['id_kelas']))
            ->assertOk()
            // Daftar jabatan tetap tampil, bukan hanya pesan kosong.
            ->assertSee('Ketua Kelas')
            ->assertSee('Bendahara')
            ->assertSee('belum diisi');
    }

    public function test_semua_jabatan_organisasi_tetap_tampil_walau_kosong(): void
    {
        $d = $this->seedPerangkat();

        DB::connection('mysql')->table('pengurus_organisasi')
            ->where('id_organisasi', $d['id_organisasi'])->delete();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('perangkat.organisasi', $d['id_organisasi']))
            ->assertOk()
            ->assertSee('Ketua OSIS')
            ->assertSee('belum diisi');
    }

    public function test_jabatan_tampil_urut_sesuai_urutan(): void
    {
        $d = $this->seedPerangkat();

        $html = $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('perangkat.kelas', $d['id_kelas']))
            ->assertOk()
            ->getContent();

        $posisiKetua = strpos($html, 'Ketua Kelas');
        $posisiWakil = strpos($html, 'Wakil Ketua');

        $this->assertNotFalse($posisiKetua);
        $this->assertNotFalse($posisiWakil);
        $this->assertLessThan($posisiWakil, $posisiKetua, 'Ketua harus tampil sebelum Wakil');
    }

    public function test_guru_bisa_melihat(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_wali_user']))
            ->get(route('perangkat.index'))
            ->assertOk();
    }

    public function test_admin_bisa_melihat(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('perangkat.index'))
            ->assertOk();
    }

    public function test_orang_tua_tidak_punya_akses(): void
    {
        $d = $this->seedPerangkat();

        $idOrtu = DB::connection('mysql')->table('users')->insertGetId([
            'username' => 'ortu_uji',
            'password_hash' => bcrypt('password'),
            'role' => 'orang_tua',
            'is_active' => true,
        ]);

        $this->actingAs($this->userDari($idOrtu))
            ->get(route('perangkat.index'))
            ->assertForbidden();

        $this->actingAs($this->userDari($idOrtu))
            ->get(route('perangkat.kelas', $d['id_kelas']))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $d = $this->seedPerangkat();

        $this->get(route('perangkat.index'))->assertRedirect(route('login'));
    }

    public function test_kelas_tidak_ada_menghasilkan_404(): void
    {
        $d = $this->seedPerangkat();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('perangkat.kelas', 999999))
            ->assertNotFound();
    }

    public function test_tahun_ajaran_lama_tetap_tersimpan(): void
    {
        $d = $this->seedPerangkat();

        // Buat tahun ajaran kedua dengan pengurus berbeda.
        $idTahunLama = DB::connection('mysql')->table('tahun_ajaran')->insertGetId([
            'nama' => '2025/2026', 'tanggal_mulai' => '2025-07-14',
            'tanggal_selesai' => '2026-06-27', 'is_aktif' => false,
        ]);

        DB::connection('mysql')->table('pengurus_organisasi')->insert([
            'id_organisasi' => $d['id_organisasi'],
            'id_tahun_ajaran' => $idTahunLama,
            'id_siswa' => $d['id_siswa'][3],
            'id_jabatan' => $d['id_jabatan_organisasi'][0],
            'bidang' => null,
        ]);

        // Lihat tahun lama: harus menampilkan pengurus tahun lama, bukan tahun aktif.
        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('perangkat.organisasi', ['id' => $d['id_organisasi'], 'tahun' => $idTahunLama]))
            ->assertOk()
            ->assertSee('2025/2026');
    }
}
