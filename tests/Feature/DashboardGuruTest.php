<?php

namespace Tests\Feature;

use App\Http\Middleware\DBDynamicConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardGuruTest extends TestCase
{
    use RefreshDatabase;


    private static int $urut = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(DBDynamicConnection::class);
    }


    private function buatGuru(string $nama = 'Guru Uji', string $nip = '1987001'): User
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => 'guru'.$nip,
            'password_hash' => bcrypt('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        DB::table('guru')->insert([
            'id_user' => $idUser,
            'nip' => $nip,
            'nama_guru' => $nama,
            'jenis_kelamin' => 'L',
        ]);

        return User::find($idUser);
    }

    public function test_halaman_dashboard_guru_tampil(): void
    {
        $user = $this->buatGuru();

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Guru Uji');
    }

    public function test_tamu_tidak_bisa_buka_dashboard_guru(): void
    {
        $this->get(route('guru.beranda'))->assertRedirect(route('login'));
    }

    public function test_peran_lain_ditolak_membuka_dashboard_guru(): void
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => 'siswauji',
            'password_hash' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->actingAs(User::find($idUser))
            ->get(route('guru.beranda'))
            ->assertForbidden();
    }

    public function test_guru_tanpa_baris_guru_ditolak_403(): void
    {

        $idUser = DB::table('users')->insertGetId([
            'username' => 'guru_kosong',
            'password_hash' => bcrypt('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->actingAs(User::find($idUser))
            ->get(route('guru.beranda'))
            ->assertForbidden();
    }

    public function test_dashboard_guru_memakai_gaya_beranda(): void
    {
        $user = $this->buatGuru();

        $html = $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('#09656F', $html);
        $this->assertStringContainsString('Figtree', $html);
        $this->assertStringContainsString('SakuSaka', $html);
        $this->assertStringContainsString('1120px', $html);
    }

    public function test_dashboard_guru_menampilkan_kartu_ringkasan(): void
    {
        $user = $this->buatGuru();

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Jadwal Hari Ini')
            ->assertSee('Sesi Terbuka')
            ->assertSee('Sesi Pekan Ini')
            ->assertSee('Kehadiran Siswa')

            ->assertDontSee('Pemilihan');
    }

    public function test_dashboard_guru_tidak_lagi_memuat_data_diri(): void
    {


        $user = $this->buatGuru('Ibu Uji', '1987002');

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Ibu Uji')
            ->assertDontSee('Profil Guru')
            ->assertDontSee('Yang bisa Anda lakukan');
    }

    public function test_guru_melihat_data_diri_di_halaman_akun(): void
    {
        $user = $this->buatGuru('Ibu Uji', '1987002');

        $this->actingAs($user)
            ->get(route('akun.profil'))
            ->assertOk()
            ->assertSee('Data Diri')
            ->assertSee('Ibu Uji')
            ->assertSee('1987002');
    }

    public function test_guru_tanpa_jadwal_tetap_tampil_dengan_keadaan_kosong(): void
    {

        $user = $this->buatGuru();

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Tidak ada jadwal mengajar untuk hari ini.');
    }

    public function test_dashboard_guru_hanya_menampilkan_ikon_yang_benar_benar_ada(): void
    {


        $user = $this->buatGuru();
        $this->jadikanPanitia($user);

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee(route('guru.presensi.index'), false)
            ->assertDontSee(route('kelola.pemilihan.index'), false)
            ->assertDontSee('Pemilihan')

            ->assertDontSee(route('siswa.beranda'), false);
    }


    private function namaTahunAjaranUnik(): string
    {
        self::$urut++;

        return sprintf('2026/%03d', self::$urut);
    }


    private function hariUntukJadwal(): string
    {
        $hariIni = $this->hariIniEnum();

        return in_array($hariIni, ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'], true)
            ? $hariIni
            : 'senin';
    }


    private function buatSiswa(string $username, string $nis): int
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => $username,
            'password_hash' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        return DB::table('siswa')->insertGetId([
            'id_user' => $idUser,
            'nis' => $nis,
            'nisn' => '00'.$nis,
            'nama_siswa' => 'Siswa '.$nis,
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);
    }


    private function jadikanPanitia(
        User $user,
        string $judul = 'Pemilihan Uji Panitia',
        string $status = 'berlangsung'
    ): int {
        $idTa = DB::table('tahun_ajaran')->insertGetId([
            'nama' => $this->namaTahunAjaranUnik(),
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_aktif' => true,
        ]);

        $idOrganisasi = DB::table('organisasi')->insertGetId([
            'nama_organisasi' => 'OSIS '.self::$urut,
            'singkatan' => 'OSIS'.self::$urut,
        ]);

        $idPemilihan = DB::table('pemilihan')->insertGetId([
            'judul' => $judul,
            'id_organisasi' => $idOrganisasi,
            'id_tahun_ajaran' => $idTa,
            'dibuat_oleh' => $user->id_user,
            'status' => $status,
            'waktu_mulai' => \Illuminate\Support\Carbon::now()->subHour(),
            'waktu_selesai' => \Illuminate\Support\Carbon::now()->addHour(),
        ]);

        DB::table('panitia_pemilihan')->insert([
            'id_pemilihan' => $idPemilihan,
            'id_user' => $user->id_user,
        ]);

        return $idPemilihan;
    }

    public function test_guru_bukan_panitia_tidak_ditawari_tautan_pemilihan(): void
    {

        $user = $this->buatGuru();

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertDontSee(route('kelola.pemilihan.index'), false)
            ->assertDontSee('Pemilihan');
    }

    public function test_guru_panitia_pun_tidak_ditawari_tautan_pemilihan(): void
    {


        $user = $this->buatGuru();
        $this->jadikanPanitia($user, 'Pemilihan Milik Saya');

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertDontSee(route('kelola.pemilihan.index'), false)
            ->assertDontSee('Pemilihan Berlangsung')
            ->assertDontSee('Pemilihan Milik Saya');
    }

    public function test_footer_guru_tidak_menyebut_pemilihan(): void
    {


        $user = $this->buatGuru();

        $html = $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Akses Cepat', $html);
        $this->assertStringNotContainsString('Pemilihan', $html);

        $this->assertStringNotContainsString('&lt;br&gt;', $html);
    }

    public function test_guru_punya_hamburger_dengan_kartu_presensi(): void
    {


        $user = $this->buatGuru();

        $html = $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<details class="nav-drawer">', $html);
        $this->assertStringContainsString('<summary class="nav-hamburger"', $html);

        $panel = $this->panelDrawer($html);
        $this->assertStringContainsString(route('guru.presensi.index'), $panel);
        $this->assertStringContainsString('Presensi', $panel);
    }

    public function test_drawer_guru_memuat_seluruh_tujuan_nav_desktop(): void
    {



        $user = $this->buatGuru();

        $html = $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->getContent();

        $panel = $this->panelDrawer($html);
        $this->assertNotSame('', $panel, 'Panel drawer guru tidak ditemukan.');

        $tujuan = [
            'Presensi'  => route('guru.presensi.index'),
            'Perangkat' => route('perangkat.index'),
            'Kas Kelas' => route('kas.index'),
            'Denah'     => route('denah.index'),
        ];

        foreach ($tujuan as $label => $url) {
            $this->assertStringContainsString($url, $panel, "Tujuan {$label} tidak ada di drawer guru.");
            $this->assertStringContainsString($label, $panel, "Label {$label} tidak ada di drawer guru.");
        }

        $this->assertSame(
            count($tujuan),
            substr_count($panel, 'class="drawer-item"'),
            'Jumlah kartu drawer guru harus sama dengan jumlah tujuan nav desktop.'
        );
    }

    public function test_drawer_guru_tanpa_baris_keterangan(): void
    {
        $user = $this->buatGuru();

        $html = $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('class="ket"', $html);
        $this->assertStringNotContainsString('Roster mengajar, buka sesi, tampilkan QR', $html);
    }

    public function test_guru_punya_tombol_profil_menuju_halaman_akun(): void
    {
        $user = $this->buatGuru();

        $html = $this->actingAs($user)
            ->get(route('guru.beranda'))
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

    public function test_drawer_guru_tetap_tidak_menawarkan_pemilihan(): void
    {

        $user = $this->buatGuru();

        $html = $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->getContent();

        $panel = $this->panelDrawer($html);
        $this->assertNotSame('', $panel, 'Panel drawer guru tidak ditemukan.');
        $this->assertStringNotContainsString('Pemilihan', $panel);
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


    private function buatJadwal(int $idGuru, string $hari, string $jamMulai = '07:30:00'): int
    {
        $idTa = DB::table('tahun_ajaran')->insertGetId([
            'nama' => $this->namaTahunAjaranUnik(),
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

        $idMapel = DB::table('mata_pelajaran')->insertGetId([
            'kode_mapel' => 'MTK',
            'nama_mapel' => 'Matematika',
        ]);

        return DB::table('jadwal_pelajaran')->insertGetId([
            'id_kelas' => $idKelas,
            'id_mapel' => $idMapel,
            'id_guru' => $idGuru,
            'semester' => 'ganjil',
            'hari' => $hari,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => '09:00:00',
        ]);
    }


    private function hariIniEnum(): string
    {
        return strtolower(\Illuminate\Support\Carbon::now()->locale('id')->isoFormat('dddd'));
    }

    private function idGuruDari(User $user): int
    {
        return (int) DB::table('guru')->where('id_user', $user->id_user)->value('id_guru');
    }

    public function test_jadwal_hari_ini_tampil_dengan_tombol_buka_sesi(): void
    {
        $user = $this->buatGuru();
        $hariIni = $this->hariIniEnum();

        if (! in_array($hariIni, ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'], true)) {
            $this->markTestSkipped('Hari ini Minggu: ENUM jadwal_pelajaran.hari tidak memuat minggu.');
        }

        $idJadwal = $this->buatJadwal($this->idGuruDari($user), $hariIni);

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('X IPA 1')
            ->assertSee('Matematika')
            ->assertSee(route('guru.presensi.buka', $idJadwal), false)
            ->assertSee('Buka sesi');
    }

    public function test_tombol_buka_sesi_tidak_muncul_di_hari_yang_bukan_jadwal(): void
    {

        $user = $this->buatGuru();
        $hariIni = $this->hariIniEnum();
        $hariLain = $hariIni === 'senin' ? 'selasa' : 'senin';

        $idJadwal = $this->buatJadwal($this->idGuruDari($user), $hariLain);



        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Tidak ada jadwal')
            ->assertDontSee(route('guru.presensi.buka', $idJadwal), false);
    }

    public function test_sesi_hari_ini_menampilkan_tombol_qr_dan_tutup_bukan_buka(): void
    {

        $user = $this->buatGuru();
        $idGuru = $this->idGuruDari($user);
        $hariIni = $this->hariIniEnum();

        if (! in_array($hariIni, ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'], true)) {
            $this->markTestSkipped('Hari ini Minggu: ENUM jadwal_pelajaran.hari tidak memuat minggu.');
        }

        $idJadwal = $this->buatJadwal($idGuru, $hariIni);

        $idSesi = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => $idJadwal,
            'tanggal' => \Illuminate\Support\Carbon::now()->toDateString(),
            'dibuka_oleh' => $idGuru,
            'dibuka_pada' => \Illuminate\Support\Carbon::now(),
            'status' => 'dibuka',
        ]);

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee(route('guru.presensi.sesi', $idSesi), false)
            ->assertSee(route('guru.presensi.tutup', $idSesi), false)
            ->assertDontSee(route('guru.presensi.buka', $idJadwal), false);
    }

    public function test_jadwal_guru_lain_tidak_tampil(): void
    {

        $userA = $this->buatGuru('Guru A', '1987003');
        $userB = $this->buatGuru('Guru B', '1987004');
        $hariIni = $this->hariIniEnum();

        $hariUntukJadwal = in_array($hariIni, ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'], true)
            ? $hariIni
            : 'senin';

        $this->buatJadwal($this->idGuruDari($userB), $hariUntukJadwal);

        $this->actingAs($userA)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Tidak ada jadwal');
    }

    public function test_rekap_sesi_terakhir_tampil(): void
    {
        $user = $this->buatGuru();
        $idGuru = $this->idGuruDari($user);
        $hariIni = $this->hariIniEnum();

        $hariUntukJadwal = in_array($hariIni, ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'], true)
            ? $hariIni
            : 'senin';

        $idJadwal = $this->buatJadwal($idGuru, $hariUntukJadwal);

        $idSesi = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => $idJadwal,
            'tanggal' => \Illuminate\Support\Carbon::now()->toDateString(),
            'dibuka_oleh' => $idGuru,
            'dibuka_pada' => \Illuminate\Support\Carbon::now(),
            'status' => 'ditutup',
        ]);

        $idSiswa = DB::table('siswa')->insertGetId([
            'id_user' => DB::table('users')->insertGetId([
                'username' => 'siswa_rekap',
                'password_hash' => bcrypt('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]),
            'nis' => '20260100',
            'nisn' => '0020260100',
            'nama_siswa' => 'Siswa Rekap',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        DB::table('presensi')->insert([
            'id_sesi' => $idSesi,
            'id_siswa' => $idSiswa,
            'status' => 'hadir',
            'metode' => 'manual',
        ]);

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Rekap Sesi Terakhir')
            ->assertSee('Matematika');
    }

    public function test_tanpa_sesi_menampilkan_keadaan_kosong(): void
    {

        $user = $this->buatGuru();

        $html = $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Belum ada sesi')
            ->getContent();

        $this->assertStringContainsString('0.00%', $html);
    }

    public function test_login_guru_diarahkan_ke_dashboard(): void
    {
        $user = $this->buatGuru('Guru Login', '1987005');

        $this->post(route('login.proses'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('guru.beranda'));
    }

    public function test_guru_yang_sudah_login_diarahkan_dari_beranda_ke_dashboard(): void
    {



        $user = $this->buatGuru();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('guru.beranda'));
    }

    public function test_kehadiran_pekan_ini_mengabaikan_sesi_yang_masih_dibuka(): void
    {


        $user = $this->buatGuru();
        $idGuru = $this->idGuruDari($user);
        $idJadwal = $this->buatJadwal($idGuru, $this->hariUntukJadwal());

        $idSesiDibuka = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => $idJadwal,
            'tanggal' => \Illuminate\Support\Carbon::now()->toDateString(),
            'dibuka_oleh' => $idGuru,
            'dibuka_pada' => \Illuminate\Support\Carbon::now(),
            'status' => 'dibuka',
        ]);

        $idSiswa = $this->buatSiswa('siswa_buka', '20260101');


        DB::table('presensi')->insert([
            'id_sesi' => $idSesiDibuka,
            'id_siswa' => $idSiswa,
            'status' => 'alpa',
            'metode' => 'manual',
        ]);

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Belum ada sesi yang tercatat.');
    }

    public function test_jadwal_dari_tahun_ajaran_lama_tidak_tampil(): void
    {


        $user = $this->buatGuru();
        $idGuru = $this->idGuruDari($user);


        $idTaLama = DB::table('tahun_ajaran')->insertGetId([
            'nama' => $this->namaTahunAjaranUnik(),
            'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2026-06-30',
            'is_aktif' => false,
        ]);

        $idKelasLama = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTaLama,
            'nama_kelas' => 'XI LAMA',
            'tingkat' => 11,
            'jurusan' => 'IPA',
        ]);

        $idMapelLama = DB::table('mata_pelajaran')->insertGetId([
            'kode_mapel' => 'OLD',
            'nama_mapel' => 'Mapel Lama',
        ]);

        DB::table('jadwal_pelajaran')->insert([
            'id_kelas' => $idKelasLama,
            'id_mapel' => $idMapelLama,
            'id_guru' => $idGuru,
            'semester' => 'ganjil',
            'hari' => $this->hariUntukJadwal(),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:30:00',
        ]);


        $this->buatJadwal($idGuru, $this->hariUntukJadwal());


        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertDontSee('XI LAMA')
            ->assertDontSee('Mapel Lama');
    }

    public function test_kehadiran_yang_dihitung_adalah_kehadiran_siswa_bukan_guru(): void
    {




        $user = $this->buatGuru();
        $idGuru = $this->idGuruDari($user);
        $idJadwal = $this->buatJadwal($idGuru, $this->hariUntukJadwal());

        $idSesi = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => $idJadwal,
            'tanggal' => \Illuminate\Support\Carbon::now()->toDateString(),
            'dibuka_oleh' => $idGuru,
            'dibuka_pada' => \Illuminate\Support\Carbon::now(),
            'status' => 'ditutup',
        ]);


        $idSiswaHadir = $this->buatSiswa('siswa_hadir', '20260201');
        $idSiswaAlpa = $this->buatSiswa('siswa_alpa', '20260202');

        DB::table('presensi')->insert([
            ['id_sesi' => $idSesi, 'id_siswa' => $idSiswaHadir, 'status' => 'hadir', 'metode' => 'manual'],
            ['id_sesi' => $idSesi, 'id_siswa' => $idSiswaAlpa, 'status' => 'alpa', 'metode' => 'manual'],
        ]);

        $html = $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('50.00%', $html);


        $this->assertSame(2, DB::table('presensi')->where('id_sesi', $idSesi)->count());
        $this->assertSame(
            0,
            DB::table('presensi')->where('id_siswa', $idGuru)->count(),
            'Tidak boleh ada baris presensi yang memperlakukan guru sebagai yang diabsen.'
        );
    }

    public function test_sesi_terbuka_yang_tertinggal_tampil_dan_bisa_ditutup(): void
    {


        $user = $this->buatGuru();
        $idGuru = $this->idGuruDari($user);
        $idJadwal = $this->buatJadwal($idGuru, $this->hariUntukJadwal());

        $idSesiKemarin = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => $idJadwal,
            'tanggal' => \Illuminate\Support\Carbon::now()->subDay()->toDateString(),
            'dibuka_oleh' => $idGuru,
            'dibuka_pada' => \Illuminate\Support\Carbon::now()->subDay(),
            'status' => 'dibuka',
        ]);

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('Sesi Masih Terbuka')
            ->assertSee(route('guru.presensi.sesi', $idSesiKemarin), false)
            ->assertSee(route('guru.presensi.tutup', $idSesiKemarin), false);
    }

    public function test_sesi_terbuka_hari_ini_tidak_dirender_dua_kali(): void
    {


        $user = $this->buatGuru();
        $idGuru = $this->idGuruDari($user);
        $hariIni = $this->hariIniEnum();

        if (! in_array($hariIni, ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'], true)) {
            $this->markTestSkipped('Hari ini Minggu: ENUM jadwal_pelajaran.hari tidak memuat minggu.');
        }

        $idJadwal = $this->buatJadwal($idGuru, $hariIni);

        $idSesi = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => $idJadwal,
            'tanggal' => \Illuminate\Support\Carbon::now()->toDateString(),
            'dibuka_oleh' => $idGuru,
            'dibuka_pada' => \Illuminate\Support\Carbon::now(),
            'status' => 'dibuka',
        ]);

        $html = $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Sesi Masih Terbuka', $html);


        $this->assertSame(
            1,
            substr_count($html, route('guru.presensi.tutup', $idSesi))
        );
    }

    public function test_tanpa_tahun_ajaran_aktif_jadwal_tetap_tampil(): void
    {


        $user = $this->buatGuru();
        $idGuru = $this->idGuruDari($user);

        $idTa = DB::table('tahun_ajaran')->insertGetId([
            'nama' => $this->namaTahunAjaranUnik(),
            'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2026-06-30',
            'is_aktif' => false,
        ]);

        $idKelas = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => $idTa,
            'nama_kelas' => 'XII TANPA AKTIF',
            'tingkat' => 12,
            'jurusan' => 'IPA',
        ]);

        $idMapel = DB::table('mata_pelajaran')->insertGetId([
            'kode_mapel' => 'TANPA',
            'nama_mapel' => 'Mapel Tanpa Aktif',
        ]);

        DB::table('jadwal_pelajaran')->insert([
            'id_kelas' => $idKelas,
            'id_mapel' => $idMapel,
            'id_guru' => $idGuru,
            'semester' => 'ganjil',
            'hari' => $this->hariUntukJadwal(),
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
        ]);

        $this->actingAs($user)
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertSee('XII TANPA AKTIF')
            ->assertSee('Mapel Tanpa Aktif');
    }
}
