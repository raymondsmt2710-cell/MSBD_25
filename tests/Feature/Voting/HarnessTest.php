<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HarnessTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_procedure_dari_migration_ada(): void
    {
        $names = array_map(fn ($r) => $r->Name, DB::select('SHOW PROCEDURE STATUS WHERE Db = DATABASE()'));

        $this->assertContains('sp_scan_presensi', $names);
    }

    public function test_seed_pemilihan_berlangsung_menghasilkan_data(): void
    {
        $data = $this->seedPemilihanBerlangsung();

        $this->assertSame('berlangsung', DB::table('pemilihan')
            ->where('id_pemilihan', $data['id_pemilihan'])->value('status'));
        $this->assertCount(2, $data['id_kandidat']);
        $this->assertCount(2, $data['id_siswa']);
        $this->assertSame(2, DB::table('daftar_pemilih')
            ->where('id_pemilihan', $data['id_pemilihan'])->count());
    }
}
