<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PortalOrangTuaTest extends TestCase
{
    use RefreshDatabase;


    private bool $mysql = false;


    private ?array $dasar = null;


    private int $urut = 0;


    private int $presensiShim = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(DBDynamicConnection::class);








        $this->mysql = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);

        if (! $this->mysql) {
            $this->pasangShimView();
            $this->pasangShimFungsi();
        }
    }


    private function pasangShimView(): void
    {


        Schema::dropIfExists('v_portal_anak_orang_tua');


        Schema::create('v_portal_anak_orang_tua', function (Blueprint $t) {
            $t->increments('id_orang_tua');
            $t->string('hubungan')->nullable();
            $t->boolean('is_kontak_utama')->default(false);
            $t->unsignedInteger('id_siswa');
            $t->string('nis', 20)->nullable();
            $t->string('nisn', 10)->nullable();
            $t->string('nama_siswa', 100)->nullable();
            $t->string('jenis_kelamin', 1)->nullable();
            $t->string('status_siswa', 20)->nullable();
            $t->string('foto_url')->nullable();
            $t->unsignedInteger('id_kelas')->nullable();
            $t->string('nama_kelas')->nullable();
            $t->integer('tingkat')->nullable();
            $t->string('jurusan')->nullable();
            $t->string('nama_tahun_ajaran')->nullable();
        });


        Schema::dropIfExists('v_rekap_presensi_siswa');
        Schema::create('v_rekap_presensi_siswa', function (Blueprint $t) {
            $t->increments('id_presensi');
            $t->unsignedInteger('id_sesi');
            $t->unsignedInteger('id_siswa');
            $t->string('nis', 20)->nullable();
            $t->string('nama_siswa', 100)->nullable();
            $t->unsignedInteger('id_kelas')->nullable();
            $t->string('nama_kelas')->nullable();
            $t->string('nama_mapel')->nullable();
            $t->date('tanggal')->nullable();
            $t->string('hari')->nullable();
            $t->string('jam_mulai')->nullable();
            $t->string('jam_selesai')->nullable();
            $t->string('status')->nullable();
            $t->string('metode')->nullable();
            $t->string('waktu_presensi')->nullable();
            $t->string('catatan')->nullable();
        });
    }


    private function pasangShimFungsi(): void
    {
        $pdo = DB::connection()->getPdo();


        if (! method_exists($pdo, 'sqliteCreateFunction')) {
            return;
        }

        $pdo->sqliteCreateFunction('fn_jumlah_presensi', function ($idSiswa, $status) {
            return (int) DB::table('v_rekap_presensi_siswa')
                ->where('id_siswa', $idSiswa)
                ->where('status', $status)
                ->count();
        }, 2);

        $pdo->sqliteCreateFunction('fn_persentase_kehadiran', function ($idSiswa) {
            $total = DB::table('v_rekap_presensi_siswa')->where('id_siswa', $idSiswa)->count();

            if ($total === 0) {
                return 0.0;
            }

            $hadir = DB::table('v_rekap_presensi_siswa')
                ->where('id_siswa', $idSiswa)
                ->where('status', 'hadir')
                ->count();

            return round($hadir * 100 / $total, 2);
        }, 1);
    }


    private function dasar(): array
    {
        if ($this->dasar !== null) {
            return $this->dasar;
        }

        $idUserGuru = DB::table('users')->insertGetId([
            'username' => 'guru_uji_ortu',
            'password_hash' => bcrypt('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $idGuru = DB::table('guru')->insertGetId([
            'id_user' => $idUserGuru,
            'nip' => '198001012005011001',
            'nama_guru' => 'Guru Uji Ortu',
            'jenis_kelamin' => 'L',
        ]);

        $idTahun = DB::table('tahun_ajaran')->insertGetId([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-13',
            'tanggal_selesai' => '2027-06-26',
            'is_aktif' => true,
        ]);

        $idKelas = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTahun,
            'nama_kelas' => 'X IPA 1',
            'tingkat' => 10,
            'jurusan' => 'IPA',
            'id_wali_kelas' => $idGuru,
        ]);

        $idMapel = DB::table('mata_pelajaran')->insertGetId([
            'kode_mapel' => 'MTK',
            'nama_mapel' => 'Matematika',
        ]);

        $idJadwal = DB::table('jadwal_pelajaran')->insertGetId([
            'id_kelas' => $idKelas,
            'id_mapel' => $idMapel,
            'id_guru' => $idGuru,
            'semester' => 'ganjil',
            'hari' => 'senin',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
        ]);

        return $this->dasar = compact('idGuru', 'idTahun', 'idKelas', 'idMapel', 'idJadwal');
    }


    private function buatKeluarga(string $nama): array
    {
        $this->urut++;

        $idUser = DB::table('users')->insertGetId([
            'username' => 'ortu_'.$nama,
            'password_hash' => bcrypt('password'),
            'role' => 'orang_tua',
            'is_active' => true,
        ]);

        $idOrangTua = DB::table('orang_tua')->insertGetId([
            'id_user' => $idUser,
            'nama' => 'Wali '.$nama,
            'no_hp' => '081200000000',
        ]);

        $idUserSiswa = DB::table('users')->insertGetId([
            'username' => 'siswa_'.$nama,
            'password_hash' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);



        $idSiswa = DB::table('siswa')->insertGetId([
            'id_user' => $idUserSiswa,
            'nis' => '9'.str_pad((string) $this->urut, 6, '0', STR_PAD_LEFT),
            'nisn' => str_pad((string) $this->urut, 10, '0', STR_PAD_LEFT),
            'nama_siswa' => 'Anak '.$nama,
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        DB::table('orang_tua_siswa')->insert([
            'id_orang_tua' => $idOrangTua,
            'id_siswa' => $idSiswa,
            'hubungan' => 'ibu',
            'is_kontak_utama' => true,
        ]);

        if ($this->mysql) {

            $dasar = $this->dasar();

            DB::table('anggota_kelas')->insert([
                'id_kelas' => $dasar['idKelas'],
                'id_siswa' => $idSiswa,
                'no_absen' => $this->urut,
            ]);
        } else {

            DB::table('v_portal_anak_orang_tua')->insert([
                'id_orang_tua' => $idOrangTua,
                'hubungan' => 'ibu',
                'is_kontak_utama' => true,
                'id_siswa' => $idSiswa,
                'nis' => '9000001',
                'nisn' => '0000000001',
                'nama_siswa' => 'Anak '.$nama,
                'jenis_kelamin' => 'L',
                'status_siswa' => 'aktif',
                'nama_kelas' => 'X IPA 1',
                'tingkat' => 10,
                'jurusan' => 'IPA',
                'nama_tahun_ajaran' => '2026/2027',
            ]);
        }

        return [User::find($idUser), $idOrangTua, $idSiswa];
    }


    private function tambahPresensi(int $idSiswa, string $status, string $tanggal): void
    {
        if (! $this->mysql) {
            $this->presensiShim++;

            DB::table('v_rekap_presensi_siswa')->insert([
                'id_presensi' => $this->presensiShim,
                'id_sesi' => 1,
                'id_siswa' => $idSiswa,
                'nis' => '9000001',
                'nama_siswa' => 'Anak',
                'nama_mapel' => 'Matematika',
                'tanggal' => $tanggal,
                'hari' => 'senin',
                'jam_mulai' => '07:00:00',
                'jam_selesai' => '08:30:00',
                'status' => $status,
                'metode' => 'manual',
                'waktu_presensi' => null,
                'catatan' => null,
            ]);

            return;
        }

        $dasar = $this->dasar();


        $idSesi = DB::table('sesi_presensi')
            ->where('id_jadwal', $dasar['idJadwal'])
            ->where('tanggal', $tanggal)
            ->value('id_sesi')
            ?? DB::table('sesi_presensi')->insertGetId([
                'id_jadwal' => $dasar['idJadwal'],
                'tanggal' => $tanggal,
                'dibuka_pada' => $tanggal.' 07:30:00',
                'ditutup_pada' => $tanggal.' 09:00:00',
                'status' => 'ditutup',
            ]);

        DB::table('presensi')->insert([
            'id_sesi' => $idSesi,
            'id_siswa' => $idSiswa,
            'status' => $status,
            'metode' => 'manual',
            'waktu_presensi' => $tanggal.' 07:35:00',
            'catatan' => null,
        ]);
    }

    public function test_orang_tua_bisa_membuka_daftar_anak(): void
    {
        [$user] = $this->buatKeluarga('a');

        $this->actingAs($user)
            ->get('/ortu')
            ->assertOk()
            ->assertSee('Portal Pemantauan Orang Tua')
            ->assertSee('Anak a');
    }

    public function test_orang_tua_bisa_membuka_detail_anaknya(): void
    {
        [$user, , $idSiswa] = $this->buatKeluarga('b');
        $this->tambahPresensi($idSiswa, 'hadir', '2026-11-10');

        $this->actingAs($user)
            ->get('/ortu/anak/'.$idSiswa)
            ->assertOk()
            ->assertSee('Anak b')
            ->assertSee('Ringkasan Kehadiran')
            ->assertSee('Matematika');
    }





    public function test_persentase_kehadiran_dihitung_stored_function(): void
    {
        [$user, , $idSiswa] = $this->buatKeluarga('j');
        $this->tambahPresensi($idSiswa, 'hadir', '2026-11-09');
        $this->tambahPresensi($idSiswa, 'alpa', '2026-11-10');

        $this->actingAs($user)
            ->get('/ortu')
            ->assertOk()
            ->assertSee('50.00%');
    }



    public function test_view_anak_berupa_view_sungguhan_bukan_tabel_shim(): void
    {
        if (! $this->mysql) {
            $this->markTestSkipped('Hanya relevan pada MySQL; sqlite memakai tabel shim.');
        }

        $jenis = DB::selectOne(
            'SELECT TABLE_TYPE AS t FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            ['v_portal_anak_orang_tua']
        );

        $this->assertNotNull($jenis, 'View v_portal_anak_orang_tua tidak ada.');
        $this->assertSame('VIEW', $jenis->t);
    }



    public function test_presensi_lewat_tabel_dasar_memicu_trigger_log(): void
    {
        if (! $this->mysql) {
            $this->markTestSkipped('Trigger hanya ada di MySQL.');
        }

        [, , $idSiswa] = $this->buatKeluarga('k');
        $this->tambahPresensi($idSiswa, 'hadir', '2026-11-11');

        $log = DB::table('log_presensi')->where('id_siswa', $idSiswa)->get();

        $this->assertCount(1, $log);
        $this->assertSame('PRESENSI_BARU', $log->first()->aksi);
        $this->assertSame('hadir', $log->first()->status_baru);
    }


    public function test_orang_tua_tidak_bisa_melihat_anak_orang_lain(): void
    {
        [$userA] = $this->buatKeluarga('c');
        [, , $idSiswaB] = $this->buatKeluarga('d');

        $this->actingAs($userA)
            ->get('/ortu/anak/'.$idSiswaB)
            ->assertNotFound();
    }

    public function test_role_lain_tidak_bisa_masuk_portal_orang_tua(): void
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => 'siswa_bukan_ortu',
            'password_hash' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->actingAs(User::find($idUser))->get('/ortu')->assertForbidden();
    }

    public function test_tidak_ada_rute_tulis_di_portal(): void
    {
        [$user] = $this->buatKeluarga('e');


        $this->actingAs($user)->post('/ortu')->assertStatus(405);
        $this->actingAs($user)->post('/ortu/anak/1')->assertStatus(405);
    }

    public function test_akun_orang_tua_tanpa_data_orang_tua_ditolak(): void
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => 'ortu_tanpa_data',
            'password_hash' => bcrypt('password'),
            'role' => 'orang_tua',
            'is_active' => true,
        ]);

        $this->actingAs(User::find($idUser))->get('/ortu')->assertForbidden();
    }

    public function test_bulan_tidak_valid_diabaikan_bukan_error(): void
    {
        [$user, , $idSiswa] = $this->buatKeluarga('f');
        $this->tambahPresensi($idSiswa, 'hadir', '2026-11-10');

        foreach (['2026-13', '2026-00', 'bukan-bulan', '2026-1', '999999999999999999999'] as $bulan) {
            $this->actingAs($user)
                ->get('/ortu/anak/'.$idSiswa.'?bulan='.urlencode($bulan))
                ->assertOk();
        }
    }

    public function test_saringan_bulan_menyaring_data(): void
    {
        [$user, , $idSiswa] = $this->buatKeluarga('g');
        $this->tambahPresensi($idSiswa, 'hadir', '2026-11-10');
        $this->tambahPresensi($idSiswa, 'alpa', '2026-10-10');


        $this->actingAs($user)
            ->get('/ortu/anak/'.$idSiswa.'?bulan=2026-10')
            ->assertOk()
            ->assertSee('2026-10-10')
            ->assertDontSee('2026-11-10');


        $this->actingAs($user)
            ->get('/ortu/anak/'.$idSiswa.'?bulan=2026-11')
            ->assertOk()
            ->assertSee('2026-11-10')
            ->assertDontSee('2026-10-10');
    }

    public function test_id_raksasa_menghasilkan_404_bukan_500(): void
    {
        [$user] = $this->buatKeluarga('h');

        $this->actingAs($user)
            ->get('/ortu/anak/99999999999999999999')
            ->assertNotFound();
    }

    public function test_id_bukan_angka_menghasilkan_404_bukan_500(): void
    {
        [$user] = $this->buatKeluarga('i');

        foreach (['abc', '-1', '1.5'] as $id) {
            $this->actingAs($user)->get('/ortu/anak/'.$id)->assertNotFound();
        }
    }
}
