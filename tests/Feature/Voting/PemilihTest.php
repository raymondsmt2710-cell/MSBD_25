<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PemilihTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_admin_mendaftarkan_seluruh_kelas_sebagai_pemilih(): void
    {
        $d = $this->seedPemilihanDraf();
        DB::connection('mysql')->table('daftar_pemilih')->where('id_pemilihan', $d['id_pemilihan'])->delete();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.pemilih.daftarkan', $d['id_pemilihan']), ['id_kelas' => [$d['id_kelas']]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertGreaterThan(0, DB::connection('mysql')->table('daftar_pemilih')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_pemilih_yang_sudah_memilih_tidak_bisa_dihapus_lewat_http(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::connection('mysql')->table('daftar_pemilih')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->where('id_siswa', $d['id_siswa'][0])
            ->update(['sudah_memilih' => 1]);

        $this->actingAs($this->userDari($d['id_admin']))
            ->delete(route('kelola.pemilihan.pemilih.hapus', [$d['id_pemilihan'], $d['id_siswa'][0]]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('daftar_pemilih', [
            'id_pemilihan' => $d['id_pemilihan'],
            'id_siswa' => $d['id_siswa'][0],
        ]);
    }

    public function test_panitia_tidak_bisa_menunjuk_panitia_lain(): void
    {
        $d = $this->seedPemilihanDraf();
        DB::connection('mysql')->table('panitia_pemilihan')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'id_user' => $d['id_panitia_user'],
        ]);

        $this->actingAs($this->userDari($d['id_panitia_user']))
            ->post(route('kelola.pemilihan.panitia.simpan', $d['id_pemilihan']), ['id_user' => $d['id_guru_lain']])
            ->assertForbidden();

        $this->assertSame(1, DB::connection('mysql')->table('panitia_pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_admin_bisa_menunjuk_panitia(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.panitia.simpan', $d['id_pemilihan']), ['id_user' => $d['id_panitia_user']])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, DB::connection('mysql')->table('panitia_pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_halaman_pemilih_menampilkan_daftar(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('kelola.pemilihan.pemilih', $d['id_pemilihan']))
            ->assertOk()
            ->assertSee('Pemilih Terdaftar');
    }
}
