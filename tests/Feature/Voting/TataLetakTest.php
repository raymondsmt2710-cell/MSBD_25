<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class TataLetakTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVotingDatabase();
    }

    public function test_grid_tidak_memaksa_semua_kartu_sama_tinggi(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $html = $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('siswa.pemilihan.pilih', $d['id_pemilihan']))
            ->assertOk()
            ->getContent();


        $this->assertMatchesRegularExpression('/\.grid\{[^}]*align-items:\s*start/', $html);


        $this->assertMatchesRegularExpression('/\.kandidat-isi \.btn\{[^}]*margin-top:\s*auto/', $html);



        $this->assertMatchesRegularExpression('/\.kandidat-isi\{[^}]*flex:\s*1/', $html);
    }

    public function test_detail_kandidat_memakai_ringkasan_yang_rapi(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $html = $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('siswa.pemilihan.pilih', $d['id_pemilihan']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('detail-kandidat', $html);
        $this->assertStringContainsString('Lihat detail', $html);
    }
}
