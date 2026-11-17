<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * Regresi tata letak: kartu kandidat memakai CSS Grid, yang secara default
 * menyamakan tinggi semua kartu dalam satu baris. Akibatnya membuka "Lihat detail"
 * pada satu kartu ikut memanjangkan kartu di sebelahnya.
 *
 * Perbaikannya: `align-items:start` pada grid (kartu tidak lagi dipaksa sama
 * tinggi), plus tombol aksi menempel di dasar kartu lewat `margin-top:auto`.
 */
class TataLetakTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_grid_tidak_memaksa_semua_kartu_sama_tinggi(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $html = $this->actingAs($this->userDari($d['id_siswa_user'][0]))
            ->get(route('siswa.pemilihan.pilih', $d['id_pemilihan']))
            ->assertOk()
            ->getContent();

        // Grid harus punya align-items:start agar kartu tidak saling memanjangkan.
        $this->assertMatchesRegularExpression('/\.grid\{[^}]*align-items:\s*start/', $html);

        // Tombol aksi menempel di dasar kartu.
        $this->assertMatchesRegularExpression('/\.kandidat-isi \.btn\{[^}]*margin-top:\s*auto/', $html);

        // Isi kartu mengisi tinggi kartunya sendiri (flex:1) supaya tombol bisa
        // menempel di dasar — tetapi grid tidak boleh menyamakan tinggi antar kartu.
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
