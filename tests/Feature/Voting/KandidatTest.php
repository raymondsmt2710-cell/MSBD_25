<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KandidatTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    /** Buat siswa baru yang belum dipakai kandidat mana pun. */
    private function siswaBaru(string $username, string $nis, string $nisn): int
    {
        $idUser = DB::connection('mysql')->table('users')->insertGetId([
            'username' => $username,
            'password_hash' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        return DB::connection('mysql')->table('siswa')->insertGetId([
            'id_user' => $idUser,
            'nis' => $nis,
            'nisn' => $nisn,
            'nama_siswa' => 'Siswa '.$username,
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);
    }

    public function test_admin_menambah_kandidat_dengan_foto(): void
    {
        Storage::fake('public');
        $d = $this->seedPemilihanDraf();
        $idSiswaBaru = $this->siswaBaru('kandidat_foto', '9200001', '9200000001');

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
                'nomor_urut' => 3,
                'id_siswa_ketua' => $idSiswaBaru,
                'visi' => 'Visi',
                'misi' => 'Misi',
                'foto' => UploadedFile::fake()->image('foto.jpg', 400, 400),
            ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(3, DB::connection('mysql')->table('kandidat')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());

        $foto = DB::connection('mysql')->table('kandidat')
            ->where('id_pemilihan', $d['id_pemilihan'])->where('nomor_urut', 3)->value('foto_url');

        $this->assertNotNull($foto);
        Storage::disk('public')->assertExists($foto);
    }

    public function test_file_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');
        $d = $this->seedPemilihanDraf();
        $idSiswaBaru = $this->siswaBaru('kandidat_pdf', '9200002', '9200000002');

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
                'nomor_urut' => 3,
                'id_siswa_ketua' => $idSiswaBaru,
                'foto' => UploadedFile::fake()->create('dokumen.pdf', 100),
            ])->assertSessionHasErrors('foto');

        $this->assertSame(2, DB::connection('mysql')->table('kandidat')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_kandidat_tidak_bisa_ditambah_saat_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        $idSiswaBaru = $this->siswaBaru('kandidat_berlangsung', '9200003', '9200000003');

        // Siswa baru (bukan duplikat), jadi penolakan murni karena status pemilihan.
        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
                'nomor_urut' => 9,
                'id_siswa_ketua' => $idSiswaBaru,
            ])->assertSessionHas('error');

        $this->assertSame(2, DB::connection('mysql')->table('kandidat')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_kandidat_bersuara_tidak_bisa_dihapus_lewat_http(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::statement('CALL sp_rekam_suara(?,?,?,@x)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);

        $this->actingAs($this->userDari($d['id_admin']))
            ->delete(route('kelola.pemilihan.kandidat.hapus', [$d['id_pemilihan'], $d['id_kandidat'][0]]))
            ->assertSessionHas('error');

        $this->assertSame(2, DB::connection('mysql')->table('kandidat')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_halaman_kandidat_menampilkan_data(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('kelola.pemilihan.kandidat', $d['id_pemilihan']))
            ->assertOk()
            ->assertSee('Kandidat');
    }
}
