<?php

namespace Tests\Feature\Voting;

use App\Models\Kandidat;
use App\Models\Pemilihan;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_model_memanggil_stored_function(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->assertFalse(Pemilihan::sudahMemilih($d['id_pemilihan'], $d['id_siswa'][0]));
        $this->assertTrue(Pemilihan::bolehMemilih($d['id_pemilihan'], $d['id_siswa'][0]));
        $this->assertSame(0, Pemilihan::totalSuara($d['id_pemilihan']));
        $this->assertEqualsWithDelta(0.0, Pemilihan::partisipasi($d['id_pemilihan']), 0.001);
    }

    public function test_relasi_kandidat_ke_siswa(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $k = Kandidat::with('ketua')->find($d['id_kandidat'][0]);

        $this->assertNotNull($k->ketua);
        $this->assertNotEmpty($k->ketua->nama_siswa);
    }

    public function test_scope_berlangsung_dan_selesai(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->assertCount(1, Pemilihan::berlangsung()->get());
        $this->assertCount(0, Pemilihan::selesai()->get());
    }

    public function test_relasi_pemilihan_ke_kandidat_dan_pemilih(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $p = Pemilihan::find($d['id_pemilihan']);

        $this->assertCount(2, $p->kandidat);
        $this->assertCount(2, $p->pemilih);
    }
}
