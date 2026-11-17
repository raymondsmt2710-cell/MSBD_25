<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVotingDatabase();
    }

    public function test_seeder_idempoten(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);
        $this->seed(\Database\Seeders\PemilihanSeeder::class);

        $this->assertSame(1, DB::connection('mysql')->table('pemilihan')->count());
        $this->assertSame(3, DB::connection('mysql')->table('kandidat')->count());
        $this->assertSame(9, DB::connection('mysql')->table('siswa')->count());
    }

    public function test_akun_siswa_contoh_bisa_login(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);

        $this->assertTrue(Auth::attempt([
            'username' => 'siswa1',
            'password' => 'password',
            'is_active' => true,
        ]));
    }

    public function test_pemilihan_contoh_sedang_berlangsung(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);

        $status = DB::connection('mysql')->table('pemilihan')->value('status');
        $this->assertSame('berlangsung', $status);

        $this->assertSame(9, DB::connection('mysql')->table('daftar_pemilih')->count());
    }
}
