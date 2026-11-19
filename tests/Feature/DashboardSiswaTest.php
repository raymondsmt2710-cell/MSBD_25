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
        // View harus ada dan bisa di-query. Bila migrasi belum jalan, ini gagal
        // dengan "table doesn't exist" — persis kegagalan yang ingin dicegah.
        $this->assertSame(0, DB::table('v_perangkat_kelas_siswa')->count());
    }

    public function test_view_perangkat_organisasi_siswa_ada_dan_terbaca(): void
    {
        $this->assertSame(0, DB::table('v_perangkat_organisasi_siswa')->count());
    }

    /** Membuat satu siswa lengkap dengan akun login. Mengembalikan objek User. */
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
        // Menu Cepat kini hanya hidup di dalam drawer. Merendernya juga di halaman
        // berarti isi yang sama dua kali dan dashboard jadi panjang di ponsel.
        $user = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertDontSee('Mau ke mana?')
            ->assertDontSee('Empat pintu masuk ke fitur');
    }

    public function test_data_diri_tidak_lagi_di_dashboard(): void
    {
        // Data Diri pindah ke /akun.
        $user = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertDontSee('Profil Siswa');
    }

    public function test_drawer_tanpa_baris_keterangan(): void
    {
        // Kartu drawer setinggi satu baris: ikon + judul, tanpa keterangan.
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

        $this->assertStringContainsString(route('dashboard.presensi.riwayat').'#ringkasan', $panel);
        $this->assertStringContainsString(route('dashboard.presensi.riwayat').'#roster', $panel);
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
        // Permintaan: kelima kartu dalam satu baris ke samping, tidak turun ke
        // baris kedua, supaya mudah dilihat di ponsel.
        $user = $this->buatSiswa();

        $html = $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('scroll-snap-type:x mandatory', $html);
        $this->assertStringContainsString('overflow-x:auto', $html);
        // Lebar tetap: tanpa ini kartu menyusut dan angka "100.00%" terpotong.
        $this->assertStringContainsString('flex:0 0 120px', $html);
        // Angka terpanjang tidak boleh pecah ke baris kedua.
        $this->assertStringContainsString('white-space:nowrap', $html);
        // Desktop harus kembali jadi grid, bukan ikut mendatar.
        $this->assertStringContainsString('@media(min-width:641px)', $html);
    }

    public function test_hamburger_membuka_empat_kartu_menu_cepat(): void
    {
        $user = $this->buatSiswa();

        $html = $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->getContent();

        // Drawer memakai <details> agar bekerja tanpa JavaScript.
        $this->assertStringContainsString('<details class="nav-drawer">', $html);
        $this->assertStringContainsString('<summary class="nav-hamburger"', $html);

        // Panel drawer memuat keempat tujuan Menu Cepat.
        $panel = $this->panelDrawer($html);
        $this->assertStringContainsString(route('dashboard.presensi.riwayat'), $panel);
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

        // Tombol profil ponsel (.nav-profil) disembunyikan di desktop, jadi harus
        // ada varian yang tampil di dalam .nav-aksi — kalau tidak, /akun tidak
        // bisa dijangkau sama sekali dari layar lebar.
        $this->assertMatchesRegularExpression(
            '/<div class="nav-aksi">[\s\S]{0,600}?class="nav-profil-dalam"/',
            $html,
            'Tombol profil desktop (.nav-profil-dalam) tidak ada di dalam .nav-aksi.'
        );
    }

    /** Isi elemen .drawer-panel, dipakai untuk memeriksa isi hamburger. */
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

        // Palet dan font yang sama dengan halaman beranda.
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
        // Review Focus #1: siswa yang belum punya baris anggota_kelas tidak boleh
        // membuat halaman 500 karena nama kelas tidak ditemukan.
        $user = $this->buatSiswa('Tanpa Kelas', '20260098');

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('belum terdaftar');
    }

    public function test_alamat_panjang_dan_khusus_ter_escape(): void
    {
        // Review Focus #5: alamat berisi karakter HTML tidak boleh dieksekusi.
        // Alamat kini tampil di halaman /akun (data diri pindah dari dashboard).
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
        // Review Focus #3: siswa baru tanpa satu pun baris presensi. Stored function
        // mengembalikan 0/null; dashboard harus menampilkan 0, bukan halaman error.
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
        // Review Focus #4: tabel pengurus kosong harus memberi pesan jelas.
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

        // Siswa yang menjadi pengurus WAJIB anggota kelas tersebut
        // (dijaga trigger trg_pengurus_kelas_insert).
        DB::table('anggota_kelas')->insert([
            ['id_kelas' => $idKelas, 'id_siswa' => $idSiswaA, 'no_absen' => 1],
            ['id_kelas' => $idKelas, 'id_siswa' => $idSiswaB, 'no_absen' => 2],
        ]);

        DB::table('pengurus_kelas')->insert([
            ['id_kelas' => $idKelas, 'id_siswa' => $idSiswaA, 'id_jabatan' => $idJabatan],
            ['id_kelas' => $idKelas, 'id_siswa' => $idSiswaB, 'id_jabatan' => $idJabatan],
        ]);

        // Siswa A melihat dirinya, bukan siswa B.
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
            ->get(route('dashboard.presensi.riwayat'))
            ->assertOk()
            ->assertSee('id="ringkasan"', false)
            ->assertSee('id="roster"', false);
    }
}
