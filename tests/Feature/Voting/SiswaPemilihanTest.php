<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiswaPemilihanTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_siswa_melihat_daftar_pemilihan_yang_diikuti(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('siswa.pemilihan.index'))
            ->assertOk()
            ->assertSee('Pemilihan');
    }

    public function test_siswa_bisa_membuka_halaman_pilih(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('siswa.pemilihan.pilih', $d['id_pemilihan']))
            ->assertOk()
            ->assertSee('rahasia');
    }

    public function test_siswa_bisa_mengirim_suara(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->post(route('siswa.pemilihan.simpan', $d['id_pemilihan']), ['id_kandidat' => $d['id_kandidat'][0]])
            ->assertRedirect(route('siswa.pemilihan.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, (int) DB::connection('mysql')->selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
    }

    public function test_siswa_tidak_bisa_mengirim_suara_dua_kali_lewat_http(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        $u = $this->userDari($d['id_siswa_user'][0]);

        $this->actingAs($u)->post(route('siswa.pemilihan.simpan', $d['id_pemilihan']), ['id_kandidat' => $d['id_kandidat'][0]]);

        $this->actingAs($u)
            ->post(route('siswa.pemilihan.simpan', $d['id_pemilihan']), ['id_kandidat' => $d['id_kandidat'][1]])
            ->assertSessionHas('error');

        $this->assertSame(1, (int) DB::connection('mysql')->selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
    }

    public function test_siswa_tidak_bisa_memilih_kandidat_dari_pemilihan_lain(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->post(route('siswa.pemilihan.simpan', $d['id_pemilihan']), ['id_kandidat' => 999999])
            ->assertSessionHas('error');

        $this->assertSame(0, (int) DB::connection('mysql')->selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
    }

    public function test_siswa_tidak_bisa_memilih_saat_draf(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->post(route('siswa.pemilihan.simpan', $d['id_pemilihan']), ['id_kandidat' => $d['id_kandidat'][0]])
            ->assertSessionHas('error');

        $this->assertSame(0, (int) DB::connection('mysql')->selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
    }

    public function test_siswa_yang_bukan_pemilih_tidak_bisa_membuka_halaman_pilih(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::table('daftar_pemilih')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->where('id_siswa', $d['id_siswa'][0])
            ->delete();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('siswa.pemilihan.pilih', $d['id_pemilihan']))
            ->assertRedirect(route('siswa.pemilihan.index'))
            ->assertSessionHas('error');
    }
}
