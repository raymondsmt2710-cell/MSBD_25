<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RekamSuaraTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    private function rekam(array $d, int $idSiswa, ?int $idKandidat = null): string
    {
        DB::statement('CALL sp_rekam_suara(?,?,?,@status)', [
            $d['id_pemilihan'],
            $idSiswa,
            $idKandidat ?? $d['id_kandidat'][0],
        ]);

        return DB::selectOne('SELECT @status AS s')->s;
    }

    public function test_suara_pertama_berhasil(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->assertSame('BERHASIL', $this->rekam($d, $d['id_siswa'][0]));
        $this->assertSame(1, (int) DB::selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
        $this->assertSame(1, (int) DB::selectOne('SELECT fn_sudah_memilih(?,?) b', [$d['id_pemilihan'], $d['id_siswa'][0]])->b);
    }

    public function test_suara_kedua_ditolak(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->rekam($d, $d['id_siswa'][0]);

        $this->assertSame('SUDAH_MEMILIH', $this->rekam($d, $d['id_siswa'][0], $d['id_kandidat'][1]));
        $this->assertSame(1, (int) DB::selectOne('SELECT fn_total_suara(?) t', [$d['id_pemilihan']])->t);
    }

    public function test_bukan_pemilih_ditolak(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->assertSame('BUKAN_PEMILIH', $this->rekam($d, 999999));
    }

    public function test_kandidat_milik_pemilihan_lain_ditolak(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->assertSame('KANDIDAT_TIDAK_VALID', $this->rekam($d, $d['id_siswa'][0], 999999));
    }

    public function test_ditolak_saat_selesai(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->update(['status' => 'selesai']);

        $this->assertSame('SUDAH_DITUTUP', $this->rekam($d, $d['id_siswa'][0]));
    }

    public function test_ditolak_saat_draf(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->assertSame('BELUM_DIMULAI', $this->rekam($d, $d['id_siswa'][0]));
    }

    public function test_ditolak_di_luar_rentang_waktu(): void
    {
        // Rentang waktu tidak bisa diubah saat berlangsung (dikunci trigger), jadi
        // pemilihan yang waktunya sudah lewat dibuat langsung lewat INSERT.
        $d = $this->seedPemilihanBerlangsung();

        $idBaru = DB::table('pemilihan')->insertGetId([
            'id_organisasi' => $d['id_organisasi'],
            'id_tahun_ajaran' => $d['id_tahun_ajaran'],
            'judul' => 'Lewat Waktu',
            'waktu_mulai' => now()->subHours(2),
            'waktu_selesai' => now()->subMinute(),
            'status' => 'berlangsung',
            'dibuat_oleh' => $d['id_admin'],
        ]);

        DB::table('daftar_pemilih')->insert([
            'id_pemilihan' => $idBaru,
            'id_siswa' => $d['id_siswa'][0],
            'sudah_memilih' => 0,
        ]);

        $idKandidat = DB::table('kandidat')->insertGetId([
            'id_pemilihan' => $idBaru,
            'nomor_urut' => 1,
            'id_siswa_ketua' => $d['id_siswa'][0],
        ]);

        DB::statement('CALL sp_rekam_suara(?,?,?,@status)', [$idBaru, $d['id_siswa'][0], $idKandidat]);

        $this->assertSame('SUDAH_DITUTUP', DB::selectOne('SELECT @status AS s')->s);
        $this->assertSame(0, (int) DB::selectOne('SELECT fn_total_suara(?) t', [$idBaru])->t);
    }

    public function test_id_suara_acak_dan_tidak_berurutan(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->rekam($d, $d['id_siswa'][0]);
        $this->rekam($d, $d['id_siswa'][1]);

        $ids = DB::table('suara')->pluck('id_suara')->all();

        $this->assertCount(2, $ids);

        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
                $id
            );
        }

        $this->assertNotSame(substr($ids[0], 0, 8), substr($ids[1], 0, 8));
    }

    public function test_suara_tidak_menyimpan_identitas_siswa(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        $this->rekam($d, $d['id_siswa'][0]);

        $kolom = array_map(fn ($c) => $c->Field, DB::select('SHOW COLUMNS FROM suara'));

        $this->assertNotContains('id_siswa', $kolom);
        $this->assertNotContains('created_at', $kolom);
    }
}
