<?php

namespace Tests\Feature\Organisasi;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JabatanSeederTest extends TestCase
{
    use DatabaseTruncation;
    use OrganisasiTestSupport;

    public function test_seeder_membuat_jabatan_kelas_dan_organisasi(): void
    {
        $this->seed(\Database\Seeders\JabatanSeeder::class);

        $kelas = DB::connection('mysql')->table('jabatan')->where('lingkup', 'kelas')->count();
        $organisasi = DB::connection('mysql')->table('jabatan')->where('lingkup', 'organisasi')->count();

        $this->assertGreaterThanOrEqual(4, $kelas);
        $this->assertGreaterThanOrEqual(4, $organisasi);
    }

    public function test_seeder_idempoten(): void
    {
        $this->seed(\Database\Seeders\JabatanSeeder::class);
        $jumlahPertama = DB::connection('mysql')->table('jabatan')->count();

        $this->seed(\Database\Seeders\JabatanSeeder::class);
        $jumlahKedua = DB::connection('mysql')->table('jabatan')->count();

        $this->assertSame($jumlahPertama, $jumlahKedua);
    }

    public function test_jabatan_kelas_berurutan_dari_ketua(): void
    {
        $this->seed(\Database\Seeders\JabatanSeeder::class);

        $pertama = DB::connection('mysql')->table('jabatan')
            ->where('lingkup', 'kelas')->orderBy('urutan')->first();

        $this->assertStringContainsString('Ketua', $pertama->nama_jabatan);
        $this->assertSame(1, (int) $pertama->urutan);
    }

    public function test_setiap_jabatan_punya_kuota_minimal_satu(): void
    {
        $this->seed(\Database\Seeders\JabatanSeeder::class);

        $this->assertSame(0, DB::connection('mysql')->table('jabatan')
            ->where('kuota', '<', 1)->count());
    }
}
