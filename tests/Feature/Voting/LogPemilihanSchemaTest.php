<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogPemilihanSchemaTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_tabel_log_pemilihan_punya_kolom_yang_dibutuhkan(): void
    {
        $this->assertTrue(Schema::hasTable('log_pemilihan'));

        foreach (['id_log', 'id_pemilihan', 'id_user', 'aksi', 'status_lama', 'status_baru', 'keterangan', 'dibuat_pada'] as $kolom) {
            $this->assertTrue(Schema::hasColumn('log_pemilihan', $kolom), "kolom {$kolom} tidak ada");
        }
    }

    public function test_index_pemilihan_ada(): void
    {
        $indexDaftar = collect(DB::select('SHOW INDEX FROM daftar_pemilih'))->pluck('Key_name');
        $this->assertContains('idx_daftar_pemilih_status', $indexDaftar);

        $indexSuara = collect(DB::select('SHOW INDEX FROM suara'))->pluck('Key_name');
        $this->assertContains('idx_suara_pemilihan_kandidat', $indexSuara);

        $indexPemilihan = collect(DB::select('SHOW INDEX FROM pemilihan'))->pluck('Key_name');
        $this->assertContains('idx_pemilihan_status_waktu', $indexPemilihan);
    }
}
