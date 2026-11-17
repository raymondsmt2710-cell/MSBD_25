<?php

namespace Tests\Feature\Voting;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KelolaProcedureTest extends TestCase
{
    use DatabaseTruncation;
    use VotingTestSupport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVotingDatabase();
    }

    private function panggil(string $proc, array $params): string
    {
        DB::statement("CALL {$proc}(".implode(',', array_fill(0, count($params), '?')).',@s)', $params);

        return DB::selectOne('SELECT @s AS s')->s;
    }

    public function test_buka_pemilihan_mengubah_status(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->assertSame('BERHASIL', $this->panggil('sp_buka_pemilihan', [$d['id_pemilihan'], $d['id_admin']]));
        $this->assertSame('berlangsung', DB::table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->value('status'));
    }

    public function test_buka_ditolak_tanpa_kandidat(): void
    {
        $d = $this->seedPemilihanDraf();
        DB::table('kandidat')->where('id_pemilihan', $d['id_pemilihan'])->delete();

        $this->assertSame('BELUM_ADA_KANDIDAT', $this->panggil('sp_buka_pemilihan', [$d['id_pemilihan'], $d['id_admin']]));
    }

    public function test_buka_ditolak_tanpa_pemilih(): void
    {
        $d = $this->seedPemilihanDraf();
        DB::table('daftar_pemilih')->where('id_pemilihan', $d['id_pemilihan'])->delete();

        $this->assertSame('BELUM_ADA_PEMILIH', $this->panggil('sp_buka_pemilihan', [$d['id_pemilihan'], $d['id_admin']]));
    }

    public function test_buka_ditolak_bila_waktu_selesai_sudah_lewat(): void
    {
        $d = $this->seedPemilihanDraf();
        $idBaru = DB::table('pemilihan')->insertGetId([
            'id_organisasi' => $d['id_organisasi'],
            'id_tahun_ajaran' => $d['id_tahun_ajaran'],
            'judul' => 'Sudah Lewat',
            'waktu_mulai' => now()->subHours(2),
            'waktu_selesai' => now()->subMinute(),
            'status' => 'draf',
            'dibuat_oleh' => $d['id_admin'],
        ]);
        DB::table('daftar_pemilih')->insert(['id_pemilihan' => $idBaru, 'id_siswa' => $d['id_siswa'][0], 'sudah_memilih' => 0]);
        DB::table('kandidat')->insert(['id_pemilihan' => $idBaru, 'nomor_urut' => 1, 'id_siswa_ketua' => $d['id_siswa'][0]]);

        $this->assertSame('WAKTU_TIDAK_VALID', $this->panggil('sp_buka_pemilihan', [$idBaru, $d['id_admin']]));
    }

    public function test_bukan_admin_atau_panitia_ditolak(): void
    {
        $d = $this->seedPemilihanDraf();

        $this->assertSame('TIDAK_BERHAK', $this->panggil('sp_buka_pemilihan', [$d['id_pemilihan'], $d['id_siswa_user'][0]]));
    }

    public function test_panitia_boleh_mengelola_pemilihan_yang_ditugaskan(): void
    {
        $d = $this->seedPemilihanDraf();
        DB::table('panitia_pemilihan')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'id_user' => $d['id_panitia_user'],
        ]);

        $this->assertSame('BERHASIL', $this->panggil('sp_buka_pemilihan', [$d['id_pemilihan'], $d['id_panitia_user']]));
    }

    public function test_panitia_tidak_berhak_atas_pemilihan_lain(): void
    {
        $d = $this->seedPemilihanDraf();
        DB::table('panitia_pemilihan')->insert([
            'id_pemilihan' => $d['id_pemilihan'],
            'id_user' => $d['id_panitia_user'],
        ]);
        $lain = $this->seedPemilihanDraf();

        $this->assertSame('TIDAK_BERHAK', $this->panggil('sp_buka_pemilihan', [$lain['id_pemilihan'], $d['id_panitia_user']]));
    }

    public function test_tutup_pemilihan_mengembalikan_jumlah_suara(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::statement('CALL sp_rekam_suara(?,?,?,@x)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);

        DB::statement('CALL sp_tutup_pemilihan(?,?,@s,@n)', [$d['id_pemilihan'], $d['id_admin']]);

        $this->assertSame('BERHASIL', DB::selectOne('SELECT @s AS s')->s);
        $this->assertSame(1, (int) DB::selectOne('SELECT @n AS n')->n);
        $this->assertSame('selesai', DB::table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->value('status'));
    }

    public function test_kembalikan_ke_draf_ditolak_bila_sudah_ada_suara(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::statement('CALL sp_rekam_suara(?,?,?,@x)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);

        $this->assertSame('SUDAH_ADA_SUARA', $this->panggil('sp_kembalikan_ke_draf', [$d['id_pemilihan'], $d['id_admin']]));
        $this->assertSame('berlangsung', DB::table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->value('status'));
    }

    public function test_kembalikan_ke_draf_berhasil_bila_belum_ada_suara(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        $this->assertSame('BERHASIL', $this->panggil('sp_kembalikan_ke_draf', [$d['id_pemilihan'], $d['id_admin']]));
        $this->assertSame('draf', DB::table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->value('status'));
    }

    public function test_simpan_pemilihan_baru(): void
    {
        $d = $this->seedPemilihanDraf();

        DB::statement(
            'CALL sp_simpan_pemilihan(NULL,?,?,?,?,?,?,?,@s,@id)',
            [
                $d['id_admin'],
                'Pemilihan Baru',
                'Deskripsi baru',
                $d['id_organisasi'],
                $d['id_tahun_ajaran'],
                now()->addDay()->format('Y-m-d H:i:s'),
                now()->addDays(2)->format('Y-m-d H:i:s'),
            ]
        );

        $this->assertSame('BERHASIL', DB::selectOne('SELECT @s AS s')->s);
        $id = (int) DB::selectOne('SELECT @id AS id')->id;
        $this->assertSame('draf', DB::table('pemilihan')->where('id_pemilihan', $id)->value('status'));
        $this->assertSame('Pemilihan Baru', DB::table('pemilihan')->where('id_pemilihan', $id)->value('judul'));
    }

    public function test_simpan_pemilihan_ditolak_saat_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        DB::statement(
            'CALL sp_simpan_pemilihan(?,?,?,?,?,?,?,?,@s,@id)',
            [
                $d['id_pemilihan'],
                $d['id_admin'],
                'Judul Diubah',
                'X',
                $d['id_organisasi'],
                $d['id_tahun_ajaran'],
                now()->format('Y-m-d H:i:s'),
                now()->addDays(2)->format('Y-m-d H:i:s'),
            ]
        );

        $this->assertSame('STATUS_TIDAK_VALID', DB::selectOne('SELECT @s AS s')->s);
        $this->assertSame('Pemilihan Ketua OSIS Uji', DB::table('pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])->value('judul'));
    }

    public function test_simpan_kandidat_ditolak_saat_berlangsung(): void
    {
        $d = $this->seedPemilihanBerlangsung();

        DB::statement(
            'CALL sp_simpan_kandidat(NULL, ?, ?, ?, ?, NULL, NULL, NULL, NULL, @s, @id)',
            [$d['id_admin'], $d['id_pemilihan'], 9, $d['id_siswa'][0]]
        );

        $this->assertSame('STATUS_TIDAK_VALID', DB::selectOne('SELECT @s AS s')->s);
        $this->assertSame(2, DB::table('kandidat')->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_simpan_kandidat_baru_saat_draf(): void
    {
        $d = $this->seedPemilihanDraf();

        // Siswa baru, karena siswa yang sudah dipakai kandidat lain kini ditolak.
        $idSiswaBaru = DB::connection('mysql')->table('siswa')->insertGetId([
            'id_user' => DB::connection('mysql')->table('users')->insertGetId([
                'username' => 'uji_proc_kandidat',
                'password_hash' => bcrypt('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]),
            'nis' => '9400001', 'nisn' => '9400000001',
            'nama_siswa' => 'Siswa Proc Baru', 'jenis_kelamin' => 'L', 'status' => 'aktif',
        ]);

        DB::statement(
            'CALL sp_simpan_kandidat(NULL, ?, ?, ?, ?, NULL, ?, ?, NULL, @s, @id)',
            [$d['id_admin'], $d['id_pemilihan'], 3, $idSiswaBaru, 'Visi baru', 'Misi baru']
        );

        $this->assertSame('BERHASIL', DB::selectOne('SELECT @s AS s')->s);
        $this->assertSame(3, DB::table('kandidat')->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_hapus_kandidat_ditolak_bila_sudah_ada_suara(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::statement('CALL sp_rekam_suara(?,?,?,@x)', [$d['id_pemilihan'], $d['id_siswa'][0], $d['id_kandidat'][0]]);

        $this->assertSame('SUDAH_ADA_SUARA', $this->panggil('sp_hapus_kandidat', [$d['id_kandidat'][0], $d['id_admin']]));
        $this->assertSame(2, DB::table('kandidat')->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_daftarkan_pemilih_kelas_idempoten(): void
    {
        $d = $this->seedPemilihanDraf();
        DB::table('daftar_pemilih')->where('id_pemilihan', $d['id_pemilihan'])->delete();

        DB::statement('CALL sp_daftarkan_pemilih_kelas(?,?,?,@s,@n)', [$d['id_pemilihan'], $d['id_admin'], $d['id_kelas']]);
        $pertama = (int) DB::selectOne('SELECT @n AS n')->n;

        DB::statement('CALL sp_daftarkan_pemilih_kelas(?,?,?,@s,@n)', [$d['id_pemilihan'], $d['id_admin'], $d['id_kelas']]);
        $kedua = (int) DB::selectOne('SELECT @n AS n')->n;

        $this->assertGreaterThan(0, $pertama);
        $this->assertSame(0, $kedua);
    }

    public function test_hapus_pemilih_ditolak_bila_sudah_memilih(): void
    {
        $d = $this->seedPemilihanBerlangsung();
        DB::table('daftar_pemilih')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->where('id_siswa', $d['id_siswa'][0])
            ->update(['sudah_memilih' => 1]);

        $this->assertSame('SUDAH_MEMILIH', $this->panggil('sp_hapus_pemilih', [$d['id_pemilihan'], $d['id_admin'], $d['id_siswa'][0]]));
    }

    public function test_tunjuk_panitia_hanya_admin(): void
    {
        $d = $this->seedPemilihanDraf();
        DB::table('panitia_pemilihan')->insert(['id_pemilihan' => $d['id_pemilihan'], 'id_user' => $d['id_panitia_user']]);

        // Panitia tidak boleh menambah panitia lain.
        DB::statement('CALL sp_tunjuk_panitia(?,?,?,@s)', [$d['id_pemilihan'], $d['id_panitia_user'], $d['id_guru_lain']]);
        $this->assertSame('TIDAK_BERHAK', DB::selectOne('SELECT @s AS s')->s);
        $this->assertSame(1, DB::table('panitia_pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->count());

        // Admin boleh.
        DB::statement('CALL sp_tunjuk_panitia(?,?,?,@s)', [$d['id_pemilihan'], $d['id_admin'], $d['id_guru_lain']]);
        $this->assertSame('BERHASIL', DB::selectOne('SELECT @s AS s')->s);
        $this->assertSame(2, DB::table('panitia_pemilihan')->where('id_pemilihan', $d['id_pemilihan'])->count());
    }

    public function test_perubahan_status_lewat_procedure_mencatat_pelaku(): void
    {
        $d = $this->seedPemilihanDraf();

        DB::statement('CALL sp_buka_pemilihan(?,?,@s)', [$d['id_pemilihan'], $d['id_admin']]);

        $log = DB::table('log_pemilihan')
            ->where('id_pemilihan', $d['id_pemilihan'])
            ->where('aksi', 'STATUS_BERUBAH')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($d['id_admin'], (int) $log->id_user);
    }
}
