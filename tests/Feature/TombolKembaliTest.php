<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TujuanKembali;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Tombol "kembali" selalu menuju dashboard peran pengguna. Dulu tujuannya
// ditebak dari referer dan peta hierarki halaman, sehingga tidak bisa ditebak
// pengguna dan menimbulkan deretan kasus tepi (URL POST, domain lain, referer
// sama dengan halaman ini, tombol bolak-balik).
class TombolKembaliTest extends TestCase
{
    use DatabaseTruncation;

    private function buatAkun(string $role = 'siswa', string $username = 'kembali_uji'): User
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => $username,
            'password_hash' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
        ]);

        // Dashboard siswa/guru membaca baris siswa/guru milik akun; tanpa ini
        // halamannya 403 dan tes tombol kembali tidak sampai ke layout.
        if ($role === 'siswa') {
            DB::table('siswa')->insert([
                'id_user' => $idUser,
                'nis' => '2026'.$idUser,
                'nisn' => '002026'.$idUser,
                'nama_siswa' => 'Siswa Kembali',
                'jenis_kelamin' => 'L',
                'status' => 'aktif',
            ]);
        }

        if ($role === 'guru') {
            DB::table('guru')->insert([
                'id_user' => $idUser,
                'nip' => '1987'.$idUser,
                'nama_guru' => 'Guru Kembali',
                'jenis_kelamin' => 'L',
            ]);
        }

        return User::find($idUser);
    }

    /** Memanggil kelas langsung, tanpa lewat HTTP, agar referer bisa diatur. */
    private function untuk(?User $user, string $referer): ?string
    {
        $request = Request::create(route('akun.profil'), 'GET');
        $request->headers->set('referer', $referer);

        return TujuanKembali::untuk($request, $user);
    }

    public function test_tanpa_referer_tetap_ada_jalan_pulang(): void
    {
        // Pengguna yang membuka halaman dari bookmark tidak punya referer.
        // Tombolnya tetap ada dan menunjuk dashboard.
        $user = $this->buatAkun();

        $this->assertSame(route('siswa.beranda'), $this->untuk($user, ''));
    }

    public function test_referer_tidak_lagi_mempengaruhi_tujuan(): void
    {
        // Aturan baru: tujuan SELALU dashboard, apa pun referer-nya. Dulu referer
        // sah dipakai apa adanya sehingga tombol kembali berpindah-pindah tujuan.
        $user = $this->buatAkun();

        foreach ([
            route('siswa.perangkat'),
            route('siswa.pemilihan.index'),
            route('siswa.presensi.riwayat'),
            'https://situs-lain.example/halaman',
            route('akun.sandi.simpan'),
        ] as $referer) {
            $this->assertSame(
                route('siswa.beranda'),
                $this->untuk($user, $referer),
                "Referer {$referer} seharusnya tetap menuju dashboard."
            );
        }
    }

    public function test_referer_halaman_login_tetap_menuju_dashboard(): void
    {
        $user = $this->buatAkun('siswa');

        $this->assertSame(route('siswa.beranda'), $this->untuk($user, url('/login')));
    }

    public function test_referer_url_post_tidak_membuat_tombol_menunjuk_url_post(): void
    {
        // Setelah menyimpan form, referer bisa berupa URL POST. Karena referer
        // tidak dipakai lagi, tombol tidak mungkin menghasilkan 405.
        $user = $this->buatAkun('siswa');

        $this->assertSame(route('siswa.beranda'), $this->untuk($user, route('akun.sandi.simpan')));
    }

    public function test_referer_host_asing_tidak_dipakai(): void
    {
        $user = $this->buatAkun();

        $this->assertSame(
            route('siswa.beranda'),
            $this->untuk($user, 'https://situs-lain.example/halaman')
        );
    }

    public function test_dashboard_tidak_menampilkan_tombol_kembali(): void
    {
        // Dashboard adalah puncak navigasi; tombol kembali ke dirinya sendiri
        // tidak ada gunanya. Ini ditentukan TujuanKembali (null), bukan hanya
        // oleh @unless di layout.
        $user = $this->buatAkun('siswa');

        $request = Request::create(route('siswa.beranda'), 'GET');
        $request->headers->set('referer', route('siswa.perangkat'));

        $this->assertNull(TujuanKembali::untuk($request, $user));
    }

    public function test_dashboard_dengan_query_tetap_tanpa_tombol_kembali(): void
    {
        // Query string tidak boleh membuat dashboard dianggap "halaman lain".
        $user = $this->buatAkun('siswa', 'kb_dash_query');

        $request = Request::create(route('siswa.beranda').'?tab=1', 'GET');

        $this->assertNull(TujuanKembali::untuk($request, $user));
    }

    public function test_halaman_riwayat_presensi_kembali_ke_dashboard(): void
    {
        // Dulu halaman ini menuju menu presensi. Sekarang semuanya ke dashboard.
        $user = $this->buatAkun('siswa', 'kb_riwayat');

        $this->actingAs($user)
            ->withHeader('referer', route('siswa.presensi.riwayat', ['pekan' => '2026-11-09']))
            ->get(route('siswa.presensi.riwayat'))
            ->assertOk()
            ->assertSee('class="tombol-kembali"', false)
            ->assertSee('href="'.route('siswa.beranda').'"', false);
    }

    public function test_tamu_tidak_punya_tujuan(): void
    {
        $this->assertNull($this->untuk(null, route('beranda')));
    }

    public function test_beranda_tidak_menampilkan_tombol_kembali(): void
    {
        $user = $this->buatAkun('siswa');

        $this->actingAs($user)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertDontSee('class="tombol-kembali"', false);
    }

    public function test_halaman_dengan_referer_sah_menampilkan_tombol_kembali(): void
    {
        $user = $this->buatAkun('siswa');

        $this->actingAs($user)
            ->withHeader('referer', route('siswa.beranda'))
            ->get(route('siswa.perangkat'))
            ->assertOk()
            ->assertSee('class="tombol-kembali"', false)
            ->assertSee('Kembali ke Dashboard');
    }

    public function test_tombol_kembali_ada_di_semua_layout(): void
    {
        // Satu komponen dipasang di tiap layout; kalau salah satu terlewat,
        // halaman di layout itu kehilangan tombolnya.
        $guru = $this->buatAkun('guru', 'kb_guru');
        $this->actingAs($guru)
            ->withHeader('referer', route('guru.beranda'))
            ->get(route('guru.presensi.index'))
            ->assertOk()
            ->assertSee('class="tombol-kembali"', false);

        $siswa = $this->buatAkun('siswa', 'kb_siswa');
        $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.beranda'))
            ->get(route('siswa.presensi.riwayat'))
            ->assertOk()
            ->assertSee('class="tombol-kembali"', false);

        $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.beranda'))
            ->get(route('siswa.pemilihan.index'))
            ->assertOk()
            ->assertSee('class="tombol-kembali"', false);

        $admin = $this->buatAkun('admin', 'kb_admin');
        $this->actingAs($admin)
            ->withHeader('referer', route('admin.dashboard'))
            ->get(route('admin.siswa.index'))
            ->assertOk()
            ->assertSee('class="tombol-kembali"', false);
    }

    public function test_tidak_ada_dua_tombol_kembali_di_satu_halaman(): void
    {
        // Komponen layout sudah menyediakan tombolnya. Tautan manual di view
        // (dulu ada di halaman pemilihan) harus dihapus agar tidak dobel.
        $siswa = $this->buatAkun('siswa', 'kb_ganda');

        $html = $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.beranda'))
            ->get(route('siswa.pemilihan.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'class="tombol-kembali"'),
            'Halaman memuat lebih dari satu tombol kembali.'
        );
    }

    public function test_beranda_tidak_menampilkan_tombol_kembali_meski_ada_referer(): void
    {
        // Diuji dengan referer sah supaya yang gagal benar-benar pengecualian
        // beranda, bukan sekadar TujuanKembali mengembalikan null.
        $siswa = $this->buatAkun('siswa', 'kb_beranda_siswa');
        $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.perangkat'))
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertDontSee('class="tombol-kembali"', false);

        $guru = $this->buatAkun('guru', 'kb_beranda_guru');
        $this->actingAs($guru)
            ->withHeader('referer', route('guru.presensi.index'))
            ->get(route('guru.beranda'))
            ->assertOk()
            ->assertDontSee('class="tombol-kembali"', false);
    }

    public function test_halaman_turunan_tetap_punya_tombol_kembali(): void
    {
        // Pengecualian beranda tidak boleh ikut menghapus tombol di /akun dan
        // /siswa/perangkat: keduanya halaman turunan yang butuh jalan pulang.
        $siswa = $this->buatAkun('siswa', 'kb_turunan');

        $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.beranda'))
            ->get(route('siswa.perangkat'))
            ->assertOk()
            ->assertSee('class="tombol-kembali"', false);

        $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.beranda'))
            ->get(route('akun.profil'))
            ->assertOk()
            ->assertSee('class="tombol-kembali"', false);
    }

    public function test_pemilihan_punya_jalan_pasti_ke_dashboard(): void
    {
        // Halaman pemilihan punya satu tombol kembali, dan tujuannya dashboard.
        $siswa = $this->buatAkun('siswa', 'kb_pemilihan');

        $this->actingAs($siswa)
            ->get(route('siswa.pemilihan.index'))
            ->assertOk()
            ->assertSee('Kembali ke Dashboard')
            ->assertSee('href="'.route('siswa.beranda').'"', false);
    }

    public function test_tombol_kembali_selalu_menuju_dashboard_peran(): void
    {
        // Inti aturan baru: dari halaman mana pun, tombol kembali menuju
        // dashboard peran pengguna - bukan halaman induk fitur.
        $guru = $this->buatAkun('guru', 'kb_dash_guru');

        $this->actingAs($guru)
            ->withHeader('referer', route('guru.presensi.index'))
            ->get(route('guru.presensi.index'))
            ->assertOk()
            ->assertSee('href="'.route('guru.beranda').'"', false);

        $admin = $this->buatAkun('admin', 'kb_dash_admin');

        $this->actingAs($admin)
            ->withHeader('referer', route('admin.siswa.index'))
            ->get(route('admin.kelas.index'))
            ->assertOk()
            ->assertSee('href="'.route('admin.dashboard').'"', false);
    }

    public function test_footer_menempel_di_dasar_halaman(): void
    {
        // Footer menggantung di tengah layar pada halaman pendek (mis. /akun di
        // ponsel) karena body bukan flex kolom. Perbaikannya: body flex kolom
        // setinggi layar + footer{margin-top:auto}.
        $siswa = $this->buatAkun('siswa', 'kb_footer');

        $html = $this->actingAs($siswa)
            ->get(route('akun.profil'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/body\{[^}]*display:flex[^}]*flex-direction:column[^}]*min-height:100vh/',
            $html,
            'body harus flex kolom setinggi layar agar footer bisa didorong ke dasar.'
        );
        $this->assertMatchesRegularExpression(
            '/footer\{[^}]*margin-top:auto/',
            $html,
            'footer butuh margin-top:auto untuk menempel di dasar halaman.'
        );

        // Isi halaman WAJIB dibungkus .isi-utama. Tanpa pembungkus, .hero dan
        // .statistik menjadi flex item sehingga margin:auto-nya berhenti
        // meregangkan lebar: kartu statistik runtuh jadi satu kolom sempit.
        $this->assertStringContainsString('<div class="isi-utama">', $html);
        $this->assertMatchesRegularExpression(
            '/\.isi-utama\{[^}]*flex:1/',
            $html,
            '.isi-utama harus flex:1 agar mengisi tinggi dan mendorong footer ke dasar.'
        );
    }
}
