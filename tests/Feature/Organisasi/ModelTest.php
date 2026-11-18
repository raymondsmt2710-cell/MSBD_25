<?php

namespace Tests\Feature\Organisasi;

use App\Models\Jabatan;
use App\Models\PengurusKelas;
use App\Models\PengurusOrganisasi;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use DatabaseTruncation;
    use OrganisasiTestSupport;

    public function test_model_jabatan_punya_scope_lingkup(): void
    {
        $d = $this->seedPerangkat();

        $this->assertSame(4, Jabatan::lingkup('kelas')->count());
        $this->assertSame(4, Jabatan::lingkup('organisasi')->count());
    }

    public function test_model_jabatan_urut(): void
    {
        $d = $this->seedPerangkat();

        $pertama = Jabatan::lingkup('kelas')->urut()->first();

        $this->assertSame(1, (int) $pertama->urutan);
    }

    public function test_relasi_pengurus_kelas_ke_siswa_dan_jabatan(): void
    {
        $d = $this->seedPerangkat();

        $p = PengurusKelas::with(['siswa', 'jabatan', 'kelas'])->first();

        $this->assertNotNull($p->siswa);
        $this->assertNotNull($p->jabatan);
        $this->assertNotNull($p->kelas);
        $this->assertNotEmpty($p->siswa->nama_siswa);
    }

    public function test_relasi_pengurus_organisasi_ke_siswa_jabatan_tahun(): void
    {
        $d = $this->seedPerangkat();

        $p = PengurusOrganisasi::with(['siswa', 'jabatan', 'organisasi', 'tahunAjaran'])->first();

        $this->assertNotNull($p->siswa);
        $this->assertNotNull($p->jabatan);
        $this->assertNotNull($p->organisasi);
        $this->assertNotNull($p->tahunAjaran);
    }

    public function test_model_memanggil_stored_function(): void
    {
        $d = $this->seedPerangkat();

        $this->assertSame(2, PengurusKelas::jumlahUntukKelas($d['id_kelas']));
        $this->assertSame(1, PengurusOrganisasi::jumlahUntukOrganisasi($d['id_organisasi'], $d['id_tahun_ajaran']));
    }
}
