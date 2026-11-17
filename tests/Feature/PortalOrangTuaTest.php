<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Portal orang tua/wali: HANYA BACA, dan hanya anak miliknya sendiri.
//
// Tes ini berjalan di DUA driver dan keduanya harus hijau:
//
//   MySQL (phpunit.xml -> msbd_25_test)
//     View v_portal_anak_orang_tua dan stored function fn_* dibuat oleh migrasi,
//     jadi tes memakai objek SUNGGUHAN. Fixture menulis ke tabel dasar (presensi,
//     anggota_kelas) sehingga view, stored function, dan trigger ikut bekerja —
//     persis jalur produksi.
//
//   sqlite (:memory:)
//     Migrasi view sengaja dilewati (guard getDriverName), sehingga view diganti
//     TABEL bernama sama dan stored function diganti fungsi PHP. Tujuannya agar
//     lapisan otorisasi dan penanganan parameter tetap teruji tanpa MySQL.
//
// Yang diuji di sini adalah siapa boleh melihat anak siapa dan penanganan
// parameter — bagian yang paling mudah salah. Pemeriksaan "halaman benar-benar
// dirender" pada database aplikasi ada di PortalOrangTuaMysqlTest.
class PortalOrangTuaTest extends TestCase
{
    use RefreshDatabase;

    /** True bila suite berjalan di MySQL/MariaDB (mis. phpunit.xml menunjuk msbd_25_test). */
    private bool $mysql = false;

    /** Rantai FK minimal (tahun ajaran -> kelas -> mapel -> guru -> jadwal). Dibuat sekali per tes. */
    private ?array $dasar = null;

    /** Nomor urut untuk nis/nisn/username, supaya nilainya unik dan hasil tes dapat diulang. */
    private int $urut = 0;

    /** Penghitung id_presensi untuk tabel shim sqlite. */
    private int $presensiShim = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(DBDynamicConnection::class);

        // Di MySQL, view dan stored function dibuat oleh migrasi, jadi tes memakai objek
        // SUNGGUHAN — tidak ada shim. Di sqlite, migrasi itu sengaja dilewati sehingga
        // view diganti tabel bernama sama dan stored function diganti fungsi PHP.
        //
        // Shim TIDAK dipasang di MySQL: nama view sudah dipakai, sehingga Schema::create
        // gagal 1050. Lebih buruk lagi, DDL MySQL tidak bisa di-rollback, jadi view asli
        // akan hilang untuk seluruh sisa suite.
        $this->mysql = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);

        if (! $this->mysql) {
            $this->pasangShimView();
            $this->pasangShimFungsi();
        }
    }

    /** Hanya dipakai pada sqlite. */
    private function pasangShimView(): void
    {
        // dropIfExists perlu karena RefreshDatabase memakai ulang PDO :memory: yang sama
        // untuk seluruh kelas, sehingga tabel shim bisa tertinggal dari tes sebelumnya.
        Schema::dropIfExists('v_portal_anak_orang_tua');

        // Kolom mengikuti definisi v_portal_anak_orang_tua.
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

        // Kolom mengikuti definisi v_rekap_presensi_siswa.
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

    /** Stored function MySQL digantikan fungsi PHP pada koneksi sqlite. */
    private function pasangShimFungsi(): void
    {
        $pdo = DB::connection()->getPdo();

        // PDO MySQL tidak punya sqliteCreateFunction; di MySQL fungsi aslinya sudah ada.
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

    /**
     * Rantai FK minimal yang dibutuhkan VIEW pada MySQL:
     * tahun ajaran aktif -> kelas, mata pelajaran, guru, jadwal pelajaran.
     *
     * Dibuat sekali per tes (di-cache) karena setiap tes berjalan dalam transaksi
     * yang di-rollback, jadi datanya tidak pernah menumpuk antar tes.
     */
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

    /** Membuat orang tua + anak + relasi. Mengembalikan [user, id_orang_tua, id_siswa]. */
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

        // nis/nisn/username unik di MySQL, jadi nilainya diambil dari penghitung —
        // bukan random_int, agar hasil tes dapat diulang persis.
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
            // View mengambil kelas lewat anggota_kelas pada tahun ajaran aktif.
            $dasar = $this->dasar();

            DB::table('anggota_kelas')->insert([
                'id_kelas' => $dasar['idKelas'],
                'id_siswa' => $idSiswa,
                'no_absen' => $this->urut,
            ]);
        } else {
            // sqlite: view diganti tabel shim, jadi barisnya diisi langsung.
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

    /**
     * Menambah satu baris presensi.
     *
     * Di MySQL baris ditulis ke tabel dasar `presensi`; view dan stored function
     * menghitungnya sendiri — persis seperti di produksi (termasuk trigger
     * trg_after_presensi_insert yang mengisi log_presensi).
     * Di sqlite baris ditulis langsung ke tabel shim.
     */
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

        // uq_sesi_jadwal_tanggal: satu sesi per (jadwal, tanggal).
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

    // Angka persentase dihitung stored function MySQL, bukan di PHP. Di sqlite fungsi
    // itu diganti shim PHP, jadi yang diuji di sini adalah jalur MySQL yang sesungguhnya:
    // hadir dari 2 presensi = 50,00%. Kalau collation parameter salah (error 1267) atau
    // grant EXECUTE hilang, halaman ini 500 dan tes gagal.
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

    // Di MySQL, objek yang dibaca harus benar-benar VIEW hasil migrasi, bukan tabel
    // shim. Ini yang membuktikan migrasi 2026_11_17_000014 dipakai oleh tes.
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

    // Trigger trg_after_presensi_insert harus ikut terpicu saat tes menulis presensi
    // lewat tabel dasar — bukti bahwa fixture memakai jalur produksi, bukan menyuap view.
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

    // Inti keamanan fitur: angka di URL tidak boleh dipakai melihat anak orang lain.
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

        // Portal hanya-baca: POST tidak punya rute sama sekali.
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

        // Oktober: hanya baris Oktober yang tampil.
        $this->actingAs($user)
            ->get('/ortu/anak/'.$idSiswa.'?bulan=2026-10')
            ->assertOk()
            ->assertSee('2026-10-10')
            ->assertDontSee('2026-11-10');

        // November: sebaliknya.
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
