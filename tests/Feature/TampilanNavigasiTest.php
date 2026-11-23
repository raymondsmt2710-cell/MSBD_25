<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TujuanKembali;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TampilanNavigasiTest extends TestCase
{
    use DatabaseTruncation;

    private function buatAkun(string $role, string $username): User
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => $username,
            'password_hash' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
        ]);

        return User::find($idUser);
    }



    public function test_setelah_simpan_form_tombol_kembali_tetap_ada(): void
    {


        $admin = $this->buatAkun('admin', 'nav_admin_kembali');
        $halaman = route('admin.denah.index');

        $request = Request::create($halaman, 'GET');
        $request->headers->set('referer', $halaman);

        $this->assertNotNull(
            TujuanKembali::untuk($request, $admin),
            'Setelah menyimpan form, tombol Kembali harus tetap punya tujuan.'
        );
    }

    public function test_tujuan_kembali_setelah_simpan_bukan_halaman_itu_sendiri(): void
    {
        $admin = $this->buatAkun('admin', 'nav_admin_tujuan');
        $halaman = route('admin.denah.index');

        $request = Request::create($halaman, 'GET');
        $request->headers->set('referer', $halaman);

        $this->assertNotSame(
            $halaman,
            TujuanKembali::untuk($request, $admin),
            'Tombol Kembali tidak boleh menunjuk halaman yang sedang dibuka.'
        );
    }

    public function test_halaman_beranda_tetap_tanpa_tombol_kembali(): void
    {


        $siswa = $this->buatAkun('siswa', 'nav_siswa_beranda');
        DB::table('siswa')->insert([
            'id_user' => $siswa->id_user,
            'nis' => '20269901',
            'nisn' => '0020269901',
            'nama_siswa' => 'Siswa Navigasi',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $this->actingAs($siswa)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertDontSee('class="tombol-kembali"', false);
    }



    public function test_panel_admin_punya_topbar_seperti_halaman_lain(): void
    {
        $admin = $this->buatAkun('admin', 'nav_admin_topbar');

        $this->actingAs($admin)
            ->get(route('admin.kelas.index'))
            ->assertOk()
            ->assertSee('class="topbar"', false)
            ->assertSee('Sistem Informasi Sekolah')
            ->assertSee('SMA Negeri 1 Kabanjahe');
    }

    public function test_semua_halaman_admin_punya_topbar(): void
    {


        $admin = $this->buatAkun('admin', 'nav_admin_semua');

        foreach ([
            route('admin.dashboard'),
            route('admin.kelas.index'),
            route('admin.denah.index'),
            route('admin.siswa.index'),
            route('admin.mapel.index'),
        ] as $halaman) {
            $this->actingAs($admin)
                ->get($halaman)
                ->assertOk()
                ->assertSee('class="topbar"', false);
        }
    }



    public function test_admin_tidak_melihat_tautan_dashboard_di_tengah_halaman_pemilihan(): void
    {


        $admin = $this->buatAkun('admin', 'nav_admin_pemilihan');

        $html = $this->actingAs($admin)
            ->get(route('kelola.pemilihan.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            'class="nav-links"',
            $html,
            'Admin tidak boleh mendapat tautan di tengah bar pada halaman pemilihan.'
        );
    }

    public function test_admin_tetap_punya_sidebar_di_halaman_pemilihan(): void
    {

        $admin = $this->buatAkun('admin', 'nav_admin_sidebar');

        $this->actingAs($admin)
            ->get(route('kelola.pemilihan.index'))
            ->assertOk()
            ->assertSee(route('admin.dashboard'), false)
            ->assertSee('Akun Pengguna');
    }

    public function test_sidebar_admin_punya_tombol_hamburger_dan_ikon_menu(): void
    {
        $admin = $this->buatAkun('admin', 'nav_admin_hamburger');

        $html = $this->actingAs($admin)
            ->get(route('admin.kelas.index'))
            ->assertOk()
            ->getContent();



        $this->assertMatchesRegularExpression(
            '/<button[^>]*class="sidebar-toggle"[^>]*aria-expanded="false"[^>]*aria-controls="menu-admin"/',
            $html,
            'Sidebar admin butuh tombol hamburger yang bisa diakses keyboard.'
        );

        $this->assertStringContainsString('id="menu-admin"', $html);


        $this->assertMatchesRegularExpression(
            '/<a class="menu[^"]*"[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*<span>[^<]+<\/span>/s',
            $html,
            'Item menu sidebar harus memuat ikon SVG diikuti label.'
        );


        $this->assertGreaterThanOrEqual(
            16,
            substr_count($html, '<svg'),
            'Setiap item menu admin seharusnya punya ikon.'
        );



        $rute = [
            'admin.dashboard', 'admin.akun.index', 'admin.siswa.index', 'admin.guru.index',
            'admin.orang-tua.index', 'admin.tahun-ajaran.index', 'admin.mapel.index',
            'admin.jabatan.index', 'admin.kelas.index', 'admin.jadwal.index',
            'admin.organisasi.index', 'admin.pengurus.index', 'kelola.pemilihan.index',
            'admin.presensi.index', 'admin.kas.index', 'admin.denah.index',
        ];

        foreach ($rute as $nama) {
            $this->assertStringContainsString(
                'href="'.route($nama).'"',
                $html,
                "Menu admin kehilangan tautan ke rute {$nama}."
            );
        }


        $this->assertMatchesRegularExpression(
            '/<a class="menu\s+aktif"[^>]*href="'.preg_quote(route('admin.kelas.index'), '/').'"/',
            $html,
            'Item menu yang sedang dibuka harus tetap ditandai .aktif.'
        );
    }

    public function test_css_sidebar_admin_punya_aturan_hamburger(): void
    {




        $admin = $this->buatAkun('admin', 'nav_admin_css');

        $html = $this->actingAs($admin)
            ->get(route('admin.kelas.index'))
            ->assertOk()
            ->getContent();



        $this->assertMatchesRegularExpression(
            '/<div class="kerangka kerangka-admin">/',
            $html,
            'Pembungkus layout admin harus memakai kelas kerangka-admin.'
        );

        $this->assertMatchesRegularExpression(
            '/\.sidebar-terbuka\s+nav|\.sidebar-terbuka\s*>\s*nav|\.sidebar-terbuka\s+#menu-admin/',
            $html,
            'CSS harus punya aturan yang menampilkan nav saat .sidebar-terbuka.'
        );

        $this->assertMatchesRegularExpression(
            '/\.sidebar-toggle\s*\{[^}]*display\s*:\s*none/',
            $html,
            'Tombol hamburger harus disembunyikan di desktop (display:none sebagai default).'
        );

        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*860px\)/',
            $html,
            'Perilaku ponsel harus dibatasi media query 860px.'
        );





        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*860px\)[\s\S]*?grid-template-columns:\s*minmax\(0,\s*1fr\)/',
            $html,
            'Kolom grid di layar sempit harus minmax(0,1fr) agar bisa menyusut.'
        );




        $this->assertMatchesRegularExpression(
            '/\.kerangka-admin\s*>\s*aside\s*\{[^}]*position\s*:\s*relative/',
            $html,
            'Sidebar di layar sempit harus position:relative agar panel bisa mengambang.'
        );

        $this->assertMatchesRegularExpression(
            '/\.sidebar-terbuka\s+nav[^{]*\{[^}]*position\s*:\s*absolute/',
            $html,
            'Panel menu harus position:absolute supaya tidak menggeser konten.'
        );




        $this->assertMatchesRegularExpression(
            '/\.kerangka-admin\s*>\s*aside\s*\{[^}]*overflow\s*:\s*visible/',
            $html,
            'Sidebar di layar sempit harus overflow:visible agar panel mengambang tidak terpotong.'
        );
    }

    public function test_skrip_sidebar_admin_mengatur_buka_tutup_panel(): void
    {


        $admin = $this->buatAkun('admin', 'nav_admin_skrip');

        $html = $this->actingAs($admin)
            ->get(route('admin.kelas.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sidebar-terbuka', $html);
        $this->assertStringContainsString("setAttribute('aria-expanded'", $html);
        $this->assertStringContainsString("'Escape'", $html);
        $this->assertStringContainsString('toggle.focus()', $html);




        $this->assertStringNotContainsString('sidebar-terbuka-terakhir', $html);
        $this->assertStringNotContainsString("setItem('sidebar-panel", $html);
    }

    public function test_kartu_bisa_digeser_di_layout_ber_tabel(): void
    {



        $admin = $this->buatAkun('admin', 'nav_admin_tabel');

        $htmlAdmin = $this->actingAs($admin)
            ->get(route('admin.kas.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*860px\)[\s\S]*?\.kartu\{[^}]*overflow-x\s*:\s*auto/',
            $htmlAdmin,
            'Kartu di layout admin harus menjadi wadah geser horizontal di <=860px.'
        );

        $htmlPemilihan = $this->actingAs($admin)
            ->get(route('kelola.pemilihan.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/\.kartu\{[^}]*overflow-x\s*:\s*auto/',
            $htmlPemilihan,
            'Kartu di layout pemilihan harus menjadi wadah geser horizontal.'
        );



        $this->assertStringNotContainsString('min-width:max-content', $htmlAdmin);
        $this->assertStringNotContainsString('min-width:max-content', $htmlPemilihan);
    }

    public function test_layout_admin_punya_kelas_grid_dua_yang_responsif(): void
    {



        $admin = $this->buatAkun('admin', 'nav_admin_grid');

        $html = $this->actingAs($admin)
            ->get(route('admin.kelas.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/\.grid-dua\{[^}]*grid-template-columns:minmax\(0,2fr\) minmax\(280px,1fr\)/',
            $html,
            'Kelas .grid-dua harus mendefinisikan dua kolom di desktop.'
        );


        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*860px\)[\s\S]*?\.grid-dua\{[^}]*grid-template-columns:minmax\(0,1fr\)/',
            $html,
            '.grid-dua harus menjadi satu kolom di <=860px.'
        );


        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*860px\)[\s\S]*?\.filter\s*>\s*\*\s*\{[^}]*width\s*:\s*100%/',
            $html,
            'Dropdown filter harus selebar penuh di <=860px.'
        );
    }

    public function test_tujuh_halaman_admin_tidak_lagi_memakai_grid_inline(): void
    {






        $admin = $this->buatAkun('admin', 'nav_admin_grid7');
        $this->actingAs($admin);

        $view = [
            'admin.jadwal.index',
            'admin.kelas.show',
            'admin.jabatan.index',
            'admin.mapel.index',
            'admin.tahun-ajaran.index',
            'admin.pengurus.form',
            'admin.denah.kelas',
        ];

        foreach ($view as $nama) {
            $path = resource_path('views/'.str_replace('.', '/', $nama).'.blade.php');
            $this->assertFileExists($path, "View {$nama} tidak ditemukan.");

            $isi = file_get_contents($path);

            $this->assertStringNotContainsString(
                'style="grid-template-columns',
                $isi,
                "View {$nama} masih memakai grid inline; media query tidak akan berlaku."
            );


            $this->assertStringContainsString(
                'class="grid grid-dua"',
                $isi,
                "View {$nama} harus memakai class=\"grid grid-dua\"."
            );
        }
    }
}

