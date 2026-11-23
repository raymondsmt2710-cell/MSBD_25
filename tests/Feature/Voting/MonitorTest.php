<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitorTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVotingDatabase();
    }

    public function test_monitor_menampilkan_hitungan_sementara_untuk_panitia(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::statement('CALL sp_rekam_suara(?,?,?,@s)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('kelola.pemilihan.monitor', $d['id_pemilihan']))
            ->assertOk()
            ->assertSee('Monitor')
            ->assertSee('data-suarakandidat="'.$d['id_kandidat'][0].':1"', false);
    }

    public function test_admin_menutup_pemilihan_dan_hasil_terbuka(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.tutup', $d['id_pemilihan']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('selesai', DB::connection('mysql')->table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->value('status'));
    }

    public function test_admin_membuka_pemilihan(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.buka', $d['id_pemilihan']))
            ->assertSessionHas('success');

        $this->assertSame('berlangsung', DB::connection('mysql')->table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->value('status'));
    }

    public function test_peringatan_muncul_bila_waktu_sudah_lewat(): void
    {

        $d = $this->seedPemilihanBerlangsung();

        $idBaru = DB::connection('mysql')->table('pemilihan')->insertGetId([
            'id_organisasi' => $d['id_organisasi'],
            'id_tahun_ajaran' => $d['id_tahun_ajaran'],
            'judul' => 'Lewat Waktu',
            'waktu_mulai' => now()->subHours(2),
            'waktu_selesai' => now()->subMinute(),
            'status' => 'berlangsung',
            'dibuat_oleh' => $d['id_admin'],
        ]);

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('kelola.pemilihan.monitor', $idBaru))
            ->assertOk()
            ->assertSee('waktu sudah lewat');
    }

    public function test_log_menampilkan_perubahan_status(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.tutup', $d['id_pemilihan']));

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('kelola.pemilihan.log', $d['id_pemilihan']))
            ->assertOk()
            ->assertSee('STATUS_BERUBAH');
    }

    public function test_log_tidak_menyebut_kandidat(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::statement('CALL sp_rekam_suara(?,?,?,@s)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);

        $keterangan = DB::connection('mysql')->table('log_pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->pluck('keterangan')
            ->implode(' ');

        $this->assertStringContainsString('menggunakan hak pilihnya', $keterangan);
        $this->assertStringNotContainsString('kandidat', strtolower($keterangan));
    }
}
