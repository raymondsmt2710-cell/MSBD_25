<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GrantTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVotingDatabase();
    }

    public function test_siswa_tidak_bisa_insert_suara_langsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->expectException(\Throwable::class);

        DB::connection('mysql_siswa')->insert(
            'INSERT INTO suara (id_suara, id_pemilihan, id_kandidat) VALUES (?,?,?)',
            [Str::uuid()->toString(), $d['id_pemilihan'], $d['id_kandidat'][0]]
        );
    }

    public function test_siswa_tidak_bisa_update_daftar_pemilih(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->expectException(\Throwable::class);

        DB::connection('mysql_siswa')->update(
            'UPDATE daftar_pemilih SET sudah_memilih = 1 WHERE id_pemilihan = ?',
            [$d['id_pemilihan']]
        );
    }

    public function test_siswa_bisa_execute_sp_rekam_suara(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        DB::connection('mysql_siswa')->statement(
            'CALL sp_rekam_suara(?,?,?,@s)',
            [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]
        );

        $this->assertSame('BERHASIL', DB::connection('mysql_siswa')->selectOne('SELECT @s AS s')->s);
    }

    public function test_siswa_menerima_null_untuk_hitungan_sementara(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $baris = DB::connection('mysql_siswa')->select(
            'SELECT jumlah_suara FROM v_hasil_pemilihan WHERE id_pemilihan = ?',
            [$d['id_pemilihan']]
        );

        $this->assertNull($baris[0]->jumlah_suara);
    }

    public function test_siswa_tidak_bisa_membaca_view_monitor(): void
    {
        $this->expectException(\Throwable::class);

        DB::connection('mysql_siswa')->select('SELECT jumlah_suara FROM v_kandidat_pemilihan_hasil LIMIT 1');
    }

    public function test_siswa_tidak_bisa_menjalankan_procedure_kelola(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->expectException(\Throwable::class);

        DB::connection('mysql_siswa')->statement('CALL sp_buka_pemilihan(?,?,@s)', [$d['id_pemilihan'], $d['id_admin']]);
    }

    public function test_guru_bisa_membaca_view_monitor(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $baris = DB::connection('mysql_guru')->select(
            'SELECT jumlah_suara FROM v_kandidat_pemilihan_hasil WHERE id_pemilihan = ?',
            [$d['id_pemilihan']]
        );

        $this->assertCount(2, $baris);
    }

    public function test_guru_tidak_bisa_insert_suara_langsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->expectException(\Throwable::class);

        DB::connection('mysql_guru')->insert(
            'INSERT INTO suara (id_suara, id_pemilihan, id_kandidat) VALUES (?,?,?)',
            [Str::uuid()->toString(), $d['id_pemilihan'], $d['id_kandidat'][0]]
        );
    }
}
