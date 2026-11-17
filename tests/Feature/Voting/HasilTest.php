<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HasilTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_hasil_belum_tersedia_saat_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('siswa.pemilihan.hasil', $d['id_pemilihan']))
            ->assertRedirect(route('siswa.pemilihan.index'))
            ->assertSessionHas('error');
    }

    public function test_hasil_tampil_setelah_selesai(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->update(['status' => 'selesai']);

        $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('siswa.pemilihan.hasil', $d['id_pemilihan']))
            ->assertOk()
            ->assertSee('Perolehan Suara');
    }

    public function test_hasil_menampilkan_suara_yang_masuk(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::statement('CALL sp_rekam_suara(?,?,?,@x)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);
        DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->update(['status' => 'selesai']);

        $this->actingAs($this->userDari($d['id_siswa_user'][1]))
            ->get(route('siswa.pemilihan.hasil', $d['id_pemilihan']))
            ->assertOk()
            ->assertSee('Terbanyak');
    }
}
