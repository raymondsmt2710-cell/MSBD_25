<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi temuan review akhir: celah keabsahan suara yang bisa dieksploitasi
 * lewat kredensial database per role (guru_app), serta panitia yang tidak bisa
 * mengakses halaman kelola.
 */
class ReviewRegressionTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVotingDatabase();
    }

    // ---------- C1: panitia harus bisa mengakses halaman kelola ----------

    public function test_panitia_bisa_membuka_daftar_kelola(): void
    {
        $d = $this->seedPemilihanDraf();
        DB::connection('mysql')->table('panitia_pemilihan')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'id_user' => $d['id_panitia_user'],
        ]);

        $this->actingAs($this->userDari($d['id_panitia_user']))
            ->get(route('kelola.pemilihan.index'))
            ->assertOk()
            ->assertSee('Pemilihan Ketua OSIS Uji');
    }

    public function test_panitia_bisa_membuka_form_pemilihan_baru(): void
    {
        $d = $this->seedPemilihanDraf();
        DB::connection('mysql')->table('panitia_pemilihan')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'id_user' => $d['id_panitia_user'],
        ]);

        $this->actingAs($this->userDari($d['id_panitia_user']))
            ->get(route('kelola.pemilihan.form'))
            ->assertOk();
    }

    public function test_panitia_hanya_melihat_pemilihan_yang_ditugaskan(): void
    {
        $d = $this->seedPemilihanDraf();
        $lain = $this->seedPemilihanDraf();
        DB::connection('mysql')->table('panitia_pemilihan')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'id_user' => $d['id_panitia_user'],
        ]);

        $respons = $this->actingAs($this->userDari($d['id_panitia_user']))
            ->get(route('kelola.pemilihan.index'))
            ->assertOk();

        // Hanya pemilihan yang ditugaskan yang boleh muncul di daftar.
        $respons->assertSee(route('kelola.pemilihan.monitor', $d['id_pemilihan']), false);
        $respons->assertDontSee(route('kelola.pemilihan.monitor', $lain['id_pemilihan']), false);
    }

    public function test_guru_bukan_panitia_tetap_ditolak_di_daftar(): void
    {
        $this->seedPemilihanDraf();

        $this->actingAs($this->userDari(DB::connection('mysql')->table('users')
            ->where('username', 'like', '%guru_lain')->value('id_user')))
            ->get(route('kelola.pemilihan.index'))
            ->assertForbidden();
    }

    // ---------- C2: pemilihan bersuara tidak boleh dihapus ----------

    public function test_pemilihan_bersuara_tidak_bisa_dihapus(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::statement('CALL sp_rekam_suara(?,?,?,@x)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);

        $this->expectException(\Throwable::class);

        DB::connection('mysql')->table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->delete();
    }

    public function test_pemilihan_tanpa_suara_tetap_bisa_dihapus(): void
    {
        $d = $this->seedPemilihanDraf();

        DB::connection('mysql')->table('pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->delete();

        $this->assertSame(0, DB::connection('mysql')->table('pemilihan')->count());
    }

    // ---------- C3: pemilih yang sudah memilih tidak boleh di-reset ----------

    public function test_sudah_memilih_tidak_bisa_direset_ke_nol(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::statement('CALL sp_rekam_suara(?,?,?,@x)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);

        $this->expectException(\Throwable::class);

        DB::connection('mysql')->table('daftar_pemilih')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->where('id_siswa', $d['id_siswa'][0])
            ->update(['sudah_memilih' => 0, 'waktu_memilih' => null]);
    }

    public function test_status_tidak_bisa_diubah_langsung_dari_selesai_ke_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::statement('CALL sp_tutup_pemilihan(?,?,@s,@n)', [$d['id_pemilihan'], $d['id_admin']]);

        $this->expectException(\Throwable::class);

        DB::connection('mysql')->table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->update(['status' => 'berlangsung']);
    }

    public function test_kandidat_tidak_bisa_ditambah_langsung_saat_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->expectException(\Throwable::class);

        DB::connection('mysql')->table('kandidat')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'nomor_urut' => 9,
            'id_siswa_ketua' => $d['id_siswa'][0],
        ]);
    }

    public function test_pemilih_tidak_bisa_ditambah_langsung_saat_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->expectException(\Throwable::class);

        DB::connection('mysql')->table('daftar_pemilih')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'id_siswa' => $d['id_siswa'][0],
            'sudah_memilih' => 0,
        ]);
    }

    // ---------- I1: batas waktu harus konsisten ----------

    public function test_suara_ditolak_tepat_pada_waktu_selesai(): void
    {
        $d = $this->seedPemilihanDraf();

        // Pemilihan yang waktu selesainya persis "sekarang". Dibuat sebagai draf dulu
        // (trigger menolak penambahan pemilih pada pemilihan yang sudah dibuka),
        // lalu statusnya diubah ke berlangsung.
        $idBaru = DB::connection('mysql')->table('pemilihan')->insertGetId([
            'id_organisasi' => $d['id_organisasi'],
            'id_tahun_ajaran' => $d['id_tahun_ajaran'],
            'judul' => 'Tepat Batas',
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now(),
            'status' => 'draf',
            'dibuat_oleh' => $d['id_admin'],
        ]);
        DB::connection('mysql')->table('daftar_pemilih')->insert([
            'id_pemilihan' => $idBaru, 'id_siswa' => $d['id_siswa'][0], 'sudah_memilih' => 0,
        ]);
        $idKandidat = DB::connection('mysql')->table('kandidat')->insertGetId([
            'id_pemilihan' => $idBaru, 'nomor_urut' => 1, 'id_siswa_ketua' => $d['id_siswa'][0],
        ]);
        DB::connection('mysql')->table('pemilihan')
            ->where('id_pemilihan', $idBaru)->update(['status' => 'berlangsung']);

        DB::statement('CALL sp_rekam_suara(?,?,?,@s)', [$idBaru, $d['id_siswa'][0], $idKandidat]);

        $this->assertSame('SUDAH_DITUTUP', DB::selectOne('SELECT @s AS s')->s);
    }

    // ---------- I2: admin harus bisa melihat hasil ----------

    public function test_admin_bisa_membuka_halaman_hasil(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::connection('mysql')->table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->update(['status' => 'selesai']);

        $this->actingAs($this->userDari($d['id_admin']))
            ->get(route('kelola.pemilihan.hasil', $d['id_pemilihan']))
            ->assertOk()
            ->assertSee('Perolehan Suara');
    }

    // ---------- I6: nomor urut duplikat harus pesan jelas, bukan 500 ----------

    public function test_nomor_urut_duplikat_ditolak_dengan_pesan_validasi(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->actingAs($this->userDari($d['id_admin']))
            ->post(route('kelola.pemilihan.kandidat.simpan', $d['id_pemilihan']), [
                'nomor_urut' => 1,
                'id_siswa_ketua' => $d['id_siswa'][0],
            ])
            ->assertSessionHasErrors('nomor_urut');

        $this->assertSame(2, DB::connection('mysql')->table('kandidat')
            ->where('id_pemilihan', $d['id_pemilihan'])->count());
    }
}
