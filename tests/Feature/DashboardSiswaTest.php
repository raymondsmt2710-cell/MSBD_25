<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardSiswaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(DBDynamicConnection::class);
    }

    public function test_view_perangkat_kelas_siswa_ada_dan_terbaca(): void
    {


        $this->assertSame(0, DB::table('v_perangkat_kelas_siswa')->count());
    }

    public function test_view_perangkat_organisasi_siswa_ada_dan_terbaca(): void
    {
        $this->assertSame(0, DB::table('v_perangkat_organisasi_siswa')->count());
    }


    private function buatSiswa(string $nama = 'Siswa Uji', string $nis = '20260099'): \App\Models\User
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => 'uji'.$nis,
            'password_hash' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        DB::table('siswa')->insert([
            'id_user' => $idUser,
            'nis' => $nis,
            'nisn' => '00'.$nis,
            'nama_siswa' => $nama,
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        return \App\Models\User::find($idUser);
    }

    public function test_halaman_dashboard_siswa_tampil(): void
    {
        $user = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('Siswa Uji')
            ->assertSee('20260099');
    }

    public function test_dashboard_tidak_lagi_memuat_menu_cepat(): void
    {


        $user = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertDontSee('Mau ke mana?')
            ->assertDontSee('Empat pintu masuk ke fitur');
    }

    public function test_data_diri_tidak_lagi_di_dashboard(): void
    {

        $user = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertDontSee('Profil Siswa');
    }

    public function test_drawer_tanpa_baris_keterangan(): void
    {

        $user = $this->buatSiswa();

        $html = $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->getContent();

        $panel = $this->panelDrawer($html);

        $this->assertStringNotContainsString('class="ket"', $panel);
        $this->assertStringNotContainsString('Ringkasan kehadiran', $panel);
        $this->assertStringNotContainsString('Jadwal pelajaran pekan ini', $panel);
        $this->assertStringContainsString('Presensi', $panel);
        $this->assertStringContainsString('Perangkat Kelas', $panel);
    }

    public function test_drawer_memuat_empat_tujuan(): void
    {
        $user = $this->buatSiswa();

        $html = $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->getContent();

        $panel = $this->panelDrawer($html);
        $this->assertNotSame('', $panel, 'Panel drawer tidak ditemukan.');

        $this->assertStringContainsString(route('siswa.presensi.index'), $panel);
        $this->assertStringContainsString(route('siswa.presensi.riwayat').'#roster', $panel);
        $this->assertStringContainsString(route('siswa.perangkat'), $panel);
        $this->assertStringContainsString(route('siswa.pemilihan.index'), $panel);
    }

    public function test_tamu_tidak_bisa_buka_dashboard_siswa(): void
    {
        $this->get(route('siswa.beranda'))->assertRedirect(route('login'));
    }

    public function test_peran_lain_ditolak_membuka_dashboard_siswa(): void
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => 'guruuji',
            'password_hash' => bcrypt('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->actingAs(\App\Models\User::find($idUser))
            ->get(route('siswa.beranda'))
            ->assertForbidden();
    }

    public function test_kartu_kehadiran_mendatar_dan_bisa_digeser(): void
    {


        $user = $this->buatSiswa();

        $html = $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('scroll-snap-type:x mandatory', $html);
        $this->assertStringContainsString('overflow-x:auto', $html);

        $this->assertStringContainsString('flex:0 0 120px', $html);

        $this->assertStringContainsString('white-space:nowrap', $html);

        $this->assertStringContainsString('@media(min-width:641px)', $html);
    }

    public function test_hamburger_membuka_empat_kartu_menu_cepat(): void
    {
        $user = $this->buatSiswa();

        $html = $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->getContent();


        $this->assertStringContainsString('<details class="nav-drawer">', $html);
        $this->assertStringContainsString('<summary class="nav-hamburger"', $html);


        $panel = $this->panelDrawer($html);
        $this->assertStringContainsString(route('siswa.presensi.riwayat'), $panel);
        $this->assertStringContainsString(route('siswa.perangkat'), $panel);
        $this->assertStringContainsString(route('siswa.pemilihan.index'), $panel);
    }

    public function test_tombol_profil_ada_dan_menuju_halaman_akun(): void
    {
        $user = $this->buatSiswa();

        $html = $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('class="nav-profil"', false)
            ->assertSee(route('akun.profil'), false)
            ->getContent();




        $this->assertMatchesRegularExpression(
            '/<div class="nav-aksi">[\s\S]{0,600}?class="nav-profil-dalam"/',
            $html,
            'Tombol profil desktop (.nav-profil-dalam) tidak ada di dalam .nav-aksi.'
        );
    }


    private function panelDrawer(string $html): string
    {
        $mulai = strpos($html, '<div class="drawer-panel">');

        if ($mulai === false) {
            return '';
        }

        $akhir = strpos($html, '</details>', $mulai);

        return $akhir === false ? '' : substr($html, $mulai, $akhir - $mulai);
    }

    public function test_dashboard_memakai_gaya_beranda(): void
    {
        $user = $this->buatSiswa();

        $html = $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->getContent();


        $this->assertStringContainsString('#09656F', $html);
        $this->assertStringContainsString('Figtree', $html);
        $this->assertStringContainsString('SakuSaka', $html);
        $this->assertStringContainsString('1120px', $html);
    }

    public function test_dashboard_menampilkan_ringkasan_kehadiran(): void
    {
        $user = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('Kehadiran')
            ->assertSee('Hadir')
            ->assertSee('Izin')
            ->assertSee('Sakit')
            ->assertSee('Alpa');
    }

    public function test_siswa_tanpa_kelas_tetap_tampil(): void
    {


        $user = $this->buatSiswa('Tanpa Kelas', '20260098');

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('belum terdaftar');
    }

    public function test_alamat_panjang_dan_khusus_ter_escape(): void
    {


        $user = $this->buatSiswa('Uji Escape', '20260097');

        DB::table('siswa')->where('id_user', $user->id_user)->update([
            'alamat' => '<script>alert(1)</script> Jalan Panjang Sekali',
        ]);

        $this->actingAs($user)
            ->get(route('akun.profil'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_siswa_tanpa_presensi_menampilkan_nol_bukan_error(): void
    {


        $user = $this->buatSiswa('Tanpa Presensi', '20260095');

        $html = $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('0.00%', $html);
        $this->assertStringNotContainsString('null', $html);
    }

    public function test_halaman_perangkat_tampil_dengan_keadaan_kosong(): void
    {

        $user = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('siswa.perangkat'))
            ->assertOk()
            ->assertSee('Perangkat Kelas')
            ->assertSee('Perangkat Organisasi')
            ->assertSee('Belum ada data');
    }

    public function test_halaman_perangkat_hanya_menampilkan_data_milik_siswa_sendiri(): void
    {
        $userA = $this->buatSiswa('Siswa A', '20260091');
        $userB = $this->buatSiswa('Siswa B', '20260092');

        $idSiswaA = DB::table('siswa')->where('id_user', $userA->id_user)->value('id_siswa');
        $idSiswaB = DB::table('siswa')->where('id_user', $userB->id_user)->value('id_siswa');

        $idJabatan = DB::table('jabatan')->insertGetId([
            'nama_jabatan' => 'Ketua Kelas',
            'lingkup' => 'kelas',
            'kuota' => 1,
            'urutan' => 1,
        ]);

        $idTa = DB::table('tahun_ajaran')->insertGetId([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_aktif' => true,
        ]);

        $idKelas = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTa,
            'nama_kelas' => 'X IPA 1',
            'tingkat' => 10,
            'jurusan' => 'IPA',
        ]);



        DB::table('anggota_kelas')->insert([
            ['id_kelas' => $idKelas, 'id_siswa' => $idSiswaA, 'no_absen' => 1],
            ['id_kelas' => $idKelas, 'id_siswa' => $idSiswaB, 'no_absen' => 2],
        ]);

        DB::table('pengurus_kelas')->insert([
            ['id_kelas' => $idKelas, 'id_siswa' => $idSiswaA, 'id_jabatan' => $idJabatan],
            ['id_kelas' => $idKelas, 'id_siswa' => $idSiswaB, 'id_jabatan' => $idJabatan],
        ]);


        $this->actingAs($userA)
            ->get(route('siswa.perangkat'))
            ->assertOk()
            ->assertSee('Siswa A')
            ->assertDontSee('Siswa B');
    }

    public function test_login_siswa_diarahkan_ke_dashboard(): void
    {
        $this->buatSiswa('Siswa Login', '20260096');

        $this->post(route('login.proses'), [
            'username' => 'uji20260096',
            'password' => 'password',
        ])->assertRedirect(route('siswa.beranda'));
    }

    public function test_halaman_presensi_punya_jangkar_ringkasan_dan_roster(): void
    {
        $user = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('siswa.presensi.riwayat'))
            ->assertOk()
            ->assertSee('id="ringkasan"', false)
            ->assertSee('id="roster"', false);
    }



    public function test_beranda_menampilkan_jadwal_hari_ini(): void
    {


        $user = $this->buatSiswa('Siswa Jadwal', '20260101');
        $idSiswa = $user->siswa->id_siswa;

        [$idKelas, $idGuru, $idMapel] = $this->siapkanKelas($idSiswa, 'X UJI 1');

        DB::table('jadwal_pelajaran')->insert([
            'id_kelas' => $idKelas,
            'id_mapel' => $idMapel,
            'id_guru' => $idGuru,
            'semester' => 'ganjil',
            'hari' => strtolower(\Illuminate\Support\Carbon::today()->locale('id')->isoFormat('dddd')),
            'jam_mulai' => '07:30:00',
            'jam_selesai' => '09:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('Jadwal Pelajaran')
            ->assertSee('Matematika')
            ->assertSee('07:30');
    }

    public function test_beranda_menampilkan_pesan_bila_tidak_ada_jadwal(): void
    {

        $user = $this->buatSiswa('Siswa Kosong', '20260102');

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('Jadwal Pelajaran')
            ->assertSee('Tidak ada jadwal pelajaran untuk hari ini');
    }

    public function test_beranda_menampilkan_riwayat_presensi_terakhir(): void
    {
        $user = $this->buatSiswa('Siswa Riwayat', '20260103');
        $idSiswa = $user->siswa->id_siswa;

        [$idKelas, $idGuru, $idMapel] = $this->siapkanKelas($idSiswa, 'X UJI 2');

        $idJadwal = DB::table('jadwal_pelajaran')->insertGetId([
            'id_kelas' => $idKelas,
            'id_mapel' => $idMapel,
            'id_guru' => $idGuru,
            'semester' => 'ganjil',
            'hari' => 'senin',
            'jam_mulai' => '07:30:00',
            'jam_selesai' => '09:00:00',
        ]);

        $idSesi = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => $idJadwal,
            'tanggal' => '2026-11-10',
            'status' => 'ditutup',
        ]);

        DB::table('presensi')->insert([
            'id_sesi' => $idSesi,
            'id_siswa' => $idSiswa,
            'status' => 'hadir',
            'metode' => 'manual',
        ]);

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('Presensi Terakhir')
            ->assertSee('10 Nov 2026')
            ->assertSee('hadir')
            ->assertSee('Lihat semua presensi');
    }

    public function test_beranda_menampilkan_pemilihan_yang_sedang_berlangsung(): void
    {
        $user = $this->buatSiswa('Siswa Pil', '20260104');
        $idSiswa = $user->siswa->id_siswa;

        $this->siapkanPemilihan($idSiswa, 'berlangsung');

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('Pemilihan Uji')
            ->assertSee('Pilih Sekarang')


            ->assertSee('24 Nov 2026, 18:00')
            ->assertDontSee('18:i');
    }

    public function test_beranda_tidak_menampilkan_kartu_pemilihan_bila_tidak_ada(): void
    {


        $user = $this->buatSiswa('Siswa Tanpa Pil', '20260105');

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertDontSee('Pilih Sekarang');
    }

    public function test_pemilihan_yang_sudah_dipilih_menampilkan_status_tercatat(): void
    {
        $user = $this->buatSiswa('Siswa Sudah Pil', '20260106');
        $idSiswa = $user->siswa->id_siswa;

        $this->siapkanPemilihan($idSiswa, 'berlangsung', true);

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('Suara sudah tercatat')
            ->assertDontSee('Pilih Sekarang');
    }

    public function test_tabel_presensi_bertumpuk_di_ponsel_sempit(): void
    {



        $user = $this->buatSiswa('Siswa Tabel', '20260107');
        $this->siapkanPresensi($user->siswa->id_siswa);

        $html = $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-label="Mata Pelajaran"', $html);
        $this->assertStringContainsString('@media(max-width:560px)', $html);
        $this->assertStringContainsString('content:attr(data-label)', $html);



        $this->assertStringContainsString('.tabel-bertumpuk tbody td::before', $html);
        $this->assertStringContainsString('class="tabel tabel-bertumpuk"', $html);
        $this->assertStringNotContainsString('.tabel tbody td::before', $html);
    }

    public function test_tiga_bagian_beranda_berurutan(): void
    {

        $user = $this->buatSiswa('Siswa Urut', '20260108');

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSeeInOrder(['Jadwal Pelajaran', 'Presensi Terakhir']);
    }


    private function siapkanKelas(int $idSiswa, string $namaKelas): array
    {
        $idTahun = DB::table('tahun_ajaran')->insertGetId([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_aktif' => 1,
        ]);

        $idGuruUser = DB::table('users')->insertGetId([
            'username' => 'guru_'.$idSiswa,
            'password_hash' => bcrypt('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $idGuru = DB::table('guru')->insertGetId([
            'id_user' => $idGuruUser,
            'nip' => '1990'.$idSiswa,
            'nama_guru' => 'Bapak Uji',
            'jenis_kelamin' => 'L',
        ]);

        $idMapel = DB::table('mata_pelajaran')->insertGetId([
            'kode_mapel' => 'MTK'.$idSiswa,
            'nama_mapel' => 'Matematika',
        ]);

        $idKelas = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTahun,
            'nama_kelas' => $namaKelas,

            'tingkat' => 10,
            'jurusan' => 'IPA',
        ]);

        DB::table('anggota_kelas')->insert([
            'id_kelas' => $idKelas,
            'id_siswa' => $idSiswa,
            'no_absen' => 1,
        ]);

        return [$idKelas, $idGuru, $idMapel];
    }


    private function siapkanPemilihan(int $idSiswa, string $status = 'berlangsung', bool $sudahMemilih = false): int
    {
        $idTahun = DB::table('tahun_ajaran')->insertGetId([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_aktif' => 1,
        ]);

        $idOrganisasi = DB::table('organisasi')->insertGetId([
            'nama_organisasi' => 'OSIS Uji',
            'singkatan' => 'OSIS',
        ]);

        $idAdmin = DB::table('users')->where('role', 'admin')->value('id_user');

        if ($idAdmin === null) {
            $idAdmin = DB::table('users')->insertGetId([
                'username' => 'admin_uji_pil',
                'password_hash' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
            ]);
        }

        $idPemilihan = DB::table('pemilihan')->insertGetId([
            'id_organisasi' => $idOrganisasi,
            'id_tahun_ajaran' => $idTahun,
            'judul' => 'Pemilihan Uji',
            'waktu_mulai' => '2026-11-17 08:00:00',
            'waktu_selesai' => '2026-11-24 18:00:00',
            'status' => 'draf',
            'dibuat_oleh' => $idAdmin,
        ]);

        DB::table('daftar_pemilih')->insert([
            'id_pemilihan' => $idPemilihan,
            'id_siswa' => $idSiswa,
            'sudah_memilih' => $sudahMemilih ? 1 : 0,
        ]);

        if ($status !== 'draf') {
            DB::table('pemilihan')
                ->where('id_pemilihan', $idPemilihan)
                ->update(['status' => $status]);
        }

        return $idPemilihan;
    }


    private function siapkanPresensi(int $idSiswa, string $status = 'hadir'): void
    {
        [$idKelas, $idGuru, $idMapel] = $this->siapkanKelas($idSiswa, 'X UJI T');

        $idJadwal = DB::table('jadwal_pelajaran')->insertGetId([
            'id_kelas' => $idKelas,
            'id_mapel' => $idMapel,
            'id_guru' => $idGuru,
            'semester' => 'ganjil',
            'hari' => 'senin',
            'jam_mulai' => '07:30:00',
            'jam_selesai' => '09:00:00',
        ]);

        $idSesi = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => $idJadwal,
            'tanggal' => '2026-11-10',
            'status' => 'ditutup',
        ]);

        DB::table('presensi')->insert([
            'id_sesi' => $idSesi,
            'id_siswa' => $idSiswa,
            'status' => $status,
            'metode' => 'manual',
        ]);
    }
}
