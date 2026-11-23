<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TujuanKembali;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Regresi tampilan yang ditemukan saat memakai aplikasi:
//
//  1. Tombol "Kembali" hilang setelah menyimpan form. Penyebabnya bukan
//     tombolnya yang dilepas, melainkan back() yang mengembalikan pengguna ke
//     halaman yang SAMA. Browser lalu mengirim referer = URL halaman itu
//     sendiri, dan aturan "referer sama dengan halaman ini" membuat tujuan
//     kembali menjadi null.
//  2. Panel admin hanya punya bar tipis berisi breadcrumb, tanpa header seperti
//     halaman lain.
//  3. Halaman /kelola/pemilihan menampilkan tautan "Dashboard" di tengah bar
//     padahal admin sudah punya sidebar; tautan itu dobel dan membingungkan.
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

    // ---------------------------------------------------------------- 1. Kembali

    public function test_setelah_simpan_form_tombol_kembali_tetap_ada(): void
    {
        // Referer yang dikirim browser setelah back() sama dengan halaman yang
        // sedang dibuka. Dulu ini menghasilkan null sehingga tombol hilang.
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
        // Perbaikan di atas tidak boleh memunculkan tombol di beranda: beranda
        // adalah puncak navigasi.
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

    // ----------------------------------------------------------------- 2. Header

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
        // Satu partial dipasang di layout admin, jadi semua halaman di bawahnya
        // ikut mendapat header. Kalau layout admin diganti, tes ini menangkapnya.
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

    // ------------------------------------------------------- 3. Nav tengah dobel

    public function test_admin_tidak_melihat_tautan_dashboard_di_tengah_halaman_pemilihan(): void
    {
        // Admin sudah punya sidebar berisi "Dasbor". Tautan di tengah bar hanya
        // mengulang tujuan yang sama.
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
        // Menghapus tautan tengah tidak boleh ikut menghapus navigasi admin.
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

        // Tombol hamburger: <button>, bukan <div>, supaya bisa difokus & diaktifkan
        // dengan keyboard. aria-controls menunjuk nav yang dikendalikannya.
        $this->assertMatchesRegularExpression(
            '/<button[^>]*class="sidebar-toggle"[^>]*aria-expanded="false"[^>]*aria-controls="menu-admin"/',
            $html,
            'Sidebar admin butuh tombol hamburger yang bisa diakses keyboard.'
        );

        $this->assertStringContainsString('id="menu-admin"', $html);

        // Setiap item menu harus punya ikon SVG dan label terbungkus <span>.
        $this->assertMatchesRegularExpression(
            '/<a class="menu[^"]*"[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*<span>[^<]+<\/span>/s',
            $html,
            'Item menu sidebar harus memuat ikon SVG diikuti label.'
        );

        // Enam belas item menu => minimal enam belas ikon di dalam nav.
        $this->assertGreaterThanOrEqual(
            16,
            substr_count($html, '<svg'),
            'Setiap item menu admin seharusnya punya ikon.'
        );

        // Menambah ikon tidak boleh menghilangkan tujuan lama: 16 rute menu harus
        // tetap ada, dan item yang sedang dibuka tetap ditandai .aktif.
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

        // Halaman yang sedang dibuka adalah admin.kelas.index.
        $this->assertMatchesRegularExpression(
            '/<a class="menu\s+aktif"[^>]*href="'.preg_quote(route('admin.kelas.index'), '/').'"/',
            $html,
            'Item menu yang sedang dibuka harus tetap ditandai .aktif.'
        );
    }

    public function test_css_sidebar_admin_punya_aturan_hamburger(): void
    {
        // Sidebar admin dipakai empat layout, jadi aturannya harus ada di partial
        // bersama. Tes ini menjaga agar aturan responsif tidak hilang saat partial
        // dirapikan: tanpa .sidebar-terbuka, tombol hamburger tidak mengendalikan
        // apa pun; tanpa max-width, halaman melebar horizontal di ponsel.
        $admin = $this->buatAkun('admin', 'nav_admin_css');

        $html = $this->actingAs($admin)
            ->get(route('admin.kelas.index'))
            ->assertOk()
            ->getContent();

        // Layout admin WAJIB memakai kelas yang sama dengan partial CSS, kalau tidak
        // aturan responsif di bawah tidak akan pernah berlaku di /admin/*.
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

        // 1fr = minmax(auto,1fr): kolom TIDAK bisa menyusut di bawah min-content
        // isi halaman (mis. tabel lebar), sehingga seluruh grid melebar melewati
        // viewport dan sidebar ikut membengkak. minmax(0,1fr) mengizinkannya
        // menyusut sehingga tidak ada scroll horizontal.
        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*860px\)[\s\S]*?grid-template-columns:\s*minmax\(0,\s*1fr\)/',
            $html,
            'Kolom grid di layar sempit harus minmax(0,1fr) agar bisa menyusut.'
        );

        // Panel menu harus MENGAMBANG di atas konten (absolute), bukan mendorong
        // konten ke bawah. Dengan posisi statis, membuka 16 menu menggeser
        // konten sejauh ~665px sehingga pengguna kehilangan tempatnya.
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

        // Panel absolute akan TERPOTONG oleh overflow-y:auto milik aside (aturan
        // desktop), sehingga menu tidak terlihat sama sekali saat dibuka.
        // Di layar sempit aside harus overflow:visible.
        $this->assertMatchesRegularExpression(
            '/\.kerangka-admin\s*>\s*aside\s*\{[^}]*overflow\s*:\s*visible/',
            $html,
            'Sidebar di layar sempit harus overflow:visible agar panel mengambang tidak terpotong.'
        );
    }

    public function test_skrip_sidebar_admin_mengatur_buka_tutup_panel(): void
    {
        // Skrip ini yang membuat tombol hamburger berfungsi. Tanpa toggle kelas
        // .sidebar-terbuka dan pembaruan aria-expanded, tombolnya hanya hiasan.
        $admin = $this->buatAkun('admin', 'nav_admin_skrip');

        $html = $this->actingAs($admin)
            ->get(route('admin.kelas.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sidebar-terbuka', $html);
        $this->assertStringContainsString("setAttribute('aria-expanded'", $html);
        $this->assertStringContainsString("'Escape'", $html);
        $this->assertStringContainsString('toggle.focus()', $html);

        // Panel tidak boleh mengingat state terbuka antar halaman: tidak ada
        // sessionStorage/localStorage untuk state panel (yang disimpan hanya posisi
        // gulir sidebar). Tanpa ini, menu bisa "menempel" terbuka setelah navigasi.
        $this->assertStringNotContainsString('sidebar-terbuka-terakhir', $html);
        $this->assertStringNotContainsString("setItem('sidebar-panel", $html);
    }

    public function test_kartu_bisa_digeser_di_layout_ber_tabel(): void
    {
        // Tabel admin/pemilihan lebih lebar dari kartunya (mis. /kelola/pemilihan
        // 601px dalam kartu 358px). Tanpa wadah geser, tabel menembus keluar kartu
        // dan seluruh halaman ikut melebar sehingga bisa digeser ke samping.
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

        // Jangan tambahkan min-width:max-content / nowrap: keduanya memaksa teks
        // satu baris sehingga tabel justru lebih lebar dari perlu.
        $this->assertStringNotContainsString('min-width:max-content', $htmlAdmin);
        $this->assertStringNotContainsString('min-width:max-content', $htmlPemilihan);
    }
}

