<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TujuanKembali;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Tombol "kembali" mengikuti riwayat browser, tetapi tidak boleh mengarahkan
// pengguna ke URL yang tidak bisa dibuka dengan GET (405) atau ke domain lain.
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

    public function test_tanpa_referer_tidak_ada_tujuan(): void
    {
        $user = $this->buatAkun();

        $this->assertNull($this->untuk($user, ''));
    }

    public function test_referer_sah_dipakai_apa_adanya(): void
    {
        $user = $this->buatAkun();
        $asal = route('siswa.perangkat');

        $this->assertSame($asal, $this->untuk($user, $asal));
    }

    public function test_referer_halaman_login_diarahkan_ke_beranda_peran(): void
    {
        $user = $this->buatAkun('siswa');

        $this->assertSame(route('siswa.beranda'), $this->untuk($user, url('/login')));
    }

    public function test_referer_login_di_halaman_beranda_tidak_menampilkan_tombol(): void
    {
        // Kasus nyata setelah login: referer /login, dan halaman yang sedang
        // dibuka ADALAH beranda. Tujuan pengganti = beranda itu sendiri, jadi
        // tombolnya harus hilang, bukan menunjuk ke halaman yang sama.
        $user = $this->buatAkun('siswa');

        $request = Request::create(route('siswa.beranda'), 'GET');
        $request->headers->set('referer', url('/login'));

        $this->assertNull(TujuanKembali::untuk($request, $user));
    }

    public function test_referer_url_post_diarahkan_ke_beranda_peran(): void
    {
        // Setelah menyimpan form, url()->previous() menunjuk URL POST.
        // Tanpa pengaman ini, tombol kembali menghasilkan 405.
        $user = $this->buatAkun('siswa');

        $this->assertSame(route('siswa.beranda'), $this->untuk($user, route('akun.sandi.simpan')));
    }

    public function test_referer_host_asing_tidak_dipakai(): void
    {
        $user = $this->buatAkun();

        $this->assertNull($this->untuk($user, 'https://situs-lain.example/halaman'));
    }

    public function test_referer_dari_host_yang_sama_dengan_request_tetap_dipakai(): void
    {
        // Kasus nyata: aplikasi dibuka lewat 127.0.0.1 padahal APP_URL=localhost.
        // Perbandingan harus memakai host request, bukan APP_URL, supaya tombol
        // kembali tidak hilang begitu saja.
        $user = $this->buatAkun();

        $request = Request::create('http://127.0.0.1:8000/akun/profil', 'GET');
        $request->headers->set('referer', 'http://127.0.0.1:8000/dashboard/presensi');

        $this->assertSame(
            'http://127.0.0.1:8000/dashboard/presensi',
            TujuanKembali::untuk($request, $user)
        );
    }

    public function test_halaman_riwayat_presensi_selalu_kembali_ke_dashboard_presensi(): void
    {
        // Riwayat memakai navigasi ?pekan=... pada halaman yang sama. Kalau tombol
        // kembali mengikuti referer, hasilnya bolak-balik antara dua pekan tanpa
        // pernah keluar dari halaman itu.
        $user = $this->buatAkun('siswa', 'kb_riwayat');

        $this->actingAs($user)
            ->withHeader('referer', route('dashboard.presensi.riwayat', ['pekan' => '2026-11-09']))
            ->get(route('dashboard.presensi.riwayat'))
            ->assertOk()
            ->assertSee('class="tombol-kembali"', false)
            ->assertSee('href="'.route('dashboard.presensi.index').'"', false);
    }

    public function test_referer_sama_dengan_halaman_ini_tidak_dipakai(): void
    {
        // Tombol yang membawa pengguna ke halaman yang sedang dibuka itu sia-sia.
        $user = $this->buatAkun();

        $this->assertNull($this->untuk($user, route('akun.profil')));
    }

    public function test_tamu_tidak_punya_tujuan(): void
    {
        $this->assertNull($this->untuk(null, route('beranda')));
    }

    public function test_beranda_tidak_menampilkan_tombol_kembali(): void
    {
        // Setelah login, referer beranda adalah /login -> jatuh ke beranda itu
        // sendiri, jadi tombolnya tidak dirender.
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
            ->assertSee('Kembali');
    }

    public function test_tombol_kembali_ada_di_semua_layout(): void
    {
        // Satu komponen dipasang di tiap layout; kalau salah satu terlewat,
        // halaman di layout itu kehilangan tombolnya.
        $siswa = $this->buatAkun('siswa', 'kb_siswa');
        $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.beranda'))
            ->get(route('dashboard.presensi.index'))
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
        // Tombol lama buatan tangan harus hilang; satu halaman satu tombol.
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
        // Beranda adalah puncak navigasi, jadi tombol kembali tidak dipasang di
        // sana. Diuji dengan referer sah supaya yang gagal benar-benar
        // pengecualian beranda, bukan sekadar TujuanKembali mengembalikan null.
        $siswa = $this->buatAkun('siswa', 'kb_beranda_siswa');
        $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.perangkat'))
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertDontSee('class="tombol-kembali"', false);

        $guru = $this->buatAkun('guru', 'kb_beranda_guru');
        $this->actingAs($guru)
            ->withHeader('referer', route('dashboard.presensi.index'))
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
        // Tombol kembali berbasis riwayat bisa menunjuk halaman pilih yang sudah
        // tidak boleh dibuka setelah suara terkirim. Tautan ini selalu ke beranda.
        $siswa = $this->buatAkun('siswa', 'kb_pemilihan');

        $this->actingAs($siswa)
            ->get(route('siswa.pemilihan.index'))
            ->assertOk()
            ->assertSee('Kembali ke Dashboard')
            ->assertSee('href="'.route('siswa.beranda').'"', false);
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
