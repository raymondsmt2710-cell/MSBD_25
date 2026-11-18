<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PemilihanViewTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVotingDatabase();
    }

    public function test_hasil_pemilihan_null_selama_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $baris = DB::table('v_hasil_pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->get();

        $this->assertCount(2, $baris);
        $this->assertNull($baris[0]->jumlah_suara);
        $this->assertNull($baris[0]->persentase);
    }

    public function test_hasil_pemilihan_terlihat_setelah_selesai(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->update(['status' => 'selesai']);

        $baris = DB::table('v_hasil_pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->orderBy('nomor_urut')->get();

        $this->assertSame(0, (int) $baris[0]->jumlah_suara);
        $this->assertSame('0.00', $baris[0]->persentase);
    }

    public function test_kandidat_view_tidak_memuat_kolom_suara(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $kolom = array_keys((array) DB::table('v_kandidat_pemilihan')->first());

        $this->assertNotContains('jumlah_suara', $kolom);
        $this->assertNotContains('persentase', $kolom);
    }

    public function test_view_monitor_menampilkan_hitungan_saat_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::table('suara')->insert([
            'id_suara' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'id_pemilihan' => $d['id_pemilihan'],
            'id_kandidat' => $d['id_kandidat'][0],
        ]);

        $baris = DB::table('v_kandidat_pemilihan_hasil')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->orderBy('nomor_urut')
            ->get();

        $this->assertSame(1, (int) $baris[0]->jumlah_suara);
        $this->assertSame(0, (int) $baris[1]->jumlah_suara);
    }

    public function test_view_partisipasi_menghitung_pemilih(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::table('daftar_pemilih')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->where('id_siswa', $d['id_siswa'][0])
            ->update(['sudah_memilih' => 1, 'waktu_memilih' => now()]);

        $p = DB::table('v_partisipasi_pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->first();

        $this->assertSame(2, (int) $p->total_pemilih);
        $this->assertSame(1, (int) $p->sudah_memilih);
        $this->assertSame('50.00', $p->persentase);
    }
}
