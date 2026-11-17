<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PemilihanFunctionTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVotingDatabase();
    }

    public function test_total_suara_dan_partisipasi_awal_nol(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->assertSame(0, (int) DB::selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
        $this->assertSame('0.00', DB::selectOne('SELECT fn_partisipasi(?) p', [$d['id_pemilihan']])->p);
    }

    public function test_boleh_memilih_benar_untuk_pemilih_yang_belum_memilih(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->assertSame(1, (int) DB::selectOne('SELECT fn_boleh_memilih(?,?) b', [$d['id_pemilihan'], $d['id_siswa'][0]])->b);
        $this->assertSame(0, (int) DB::selectOne('SELECT fn_sudah_memilih(?,?) b', [$d['id_pemilihan'], $d['id_siswa'][0]])->b);
    }

    public function test_boleh_memilih_salah_untuk_siswa_yang_tidak_terdaftar(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->assertSame(0, (int) DB::selectOne('SELECT fn_boleh_memilih(?,?) b', [$d['id_pemilihan'], 999999])->b);
    }

    public function test_partisipasi_nol_tidak_membagi_nol(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::table('daftar_pemilih')->where('id_pemilihan', $d['id_pemilihan'])->delete();

        $this->assertSame('0.00', DB::selectOne('SELECT fn_partisipasi(?) p', [$d['id_pemilihan']])->p);
    }
}
