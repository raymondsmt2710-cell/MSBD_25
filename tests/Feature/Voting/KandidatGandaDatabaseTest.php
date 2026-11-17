<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Penjagaan di level database: siswa yang sama tidak boleh muncul dua kali
 * dalam satu pemilihan. Ini lapis kedua setelah validasi controller, agar
 * aturan tetap berlaku walaupun ada jalur tulis lain (procedure, seeder, dll).
 */
class KandidatGandaDatabaseTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    public function test_insert_langsung_ketua_ganda_ditolak_database(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->expectException(\Throwable::class);

        DB::connection('mysql')->table('kandidat')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'nomor_urut' => 9,
            'id_siswa_ketua' => $d['id_siswa'][0],   // sudah dipakai kandidat No.1
        ]);
    }

    public function test_update_langsung_ke_ketua_yang_sudah_dipakai_ditolak(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->expectException(\Throwable::class);

        DB::connection('mysql')->table('kandidat')
            ->where('id_kandidat', $d['id_kandidat'][1])
            ->update(['id_siswa_ketua' => $d['id_siswa'][0]]);
    }

    public function test_ketua_dan_wakil_sama_ditolak_database(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->expectException(\Throwable::class);

        DB::connection('mysql')->table('kandidat')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'nomor_urut' => 9,
            'id_siswa_ketua' => $d['id_siswa'][0],
            'id_siswa_wakil' => $d['id_siswa'][0],
        ]);
    }

    public function test_siswa_berbeda_tetap_bisa_ditambah(): void
    {
        $d = $this->seedPemilihanDraf();

        $idBaru = DB::connection('mysql')->table('siswa')->insertGetId([
            'id_user' => DB::connection('mysql')->table('users')->insertGetId([
                'username' => 'uji_db_baru',
                'password_hash' => bcrypt('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]),
            'nis' => '9100001', 'nisn' => '9100000001',
            'nama_siswa' => 'Siswa DB Baru', 'jenis_kelamin' => 'L', 'status' => 'aktif',
        ]);

        DB::connection('mysql')->table('kandidat')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'nomor_urut' => 9,
            'id_siswa_ketua' => $idBaru,
        ]);

        $this->assertSame(3, DB::connection('mysql')->table('kandidat')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());
    }
}
