<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regresi fitur presensi (fitur 1).
 *
 * Setiap tes di sini menangkap bug yang pernah lolos ke `main`, jadi pesan
 * kegagalannya menjelaskan gejala yang dilihat pengguna, bukan sekadar nama
 * method.
 */
class PresensiRegresiTest extends TestCase
{
    use DatabaseTruncation;

    private function buatSiswa(string $username = 'pr_siswa'): User
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => $username,
            'password_hash' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        DB::table('siswa')->insert([
            'id_user' => $idUser,
            'nis' => '2026'.$idUser,
            'nisn' => '002026'.$idUser,
            'nama_siswa' => 'Siswa Presensi',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        return User::find($idUser);
    }

    private function buatGuru(string $username = 'pr_guru'): User
    {
        $idUser = DB::table('users')->insertGetId([
            'username' => $username,
            'password_hash' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        DB::table('guru')->insert([
            'id_user' => $idUser,
            'nip' => '1987'.$idUser,
            'nama_guru' => 'Guru Presensi',
            'jenis_kelamin' => 'L',
        ]);

        return User::find($idUser);
    }

    /**
     * Halaman konfirmasi presensi milik SISWA tidak boleh memuat tautan ke route
     * guru. Versi lama memakai route('guru.presensi.index') sebagai tombol
     * "Kembali", sehingga siswa yang menekannya mendapat 403.
     */
    public function test_halaman_konfirmasi_siswa_tidak_menautkan_route_guru(): void
    {
        $siswa = $this->buatSiswa('pr_konfirmasi');
        $kode = str_repeat('a', 64);

        $html = $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.presensi.pindai'))
            ->get(route('siswa.presensi.konfirmasi', $kode))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            route('guru.presensi.index'),
            $html,
            'Halaman siswa memuat tautan ke halaman guru; siswa akan mendapat 403.'
        );
    }

    /**
     * Satu halaman satu tombol kembali. Halaman konfirmasi dulu merender tombol
     * dari komponen layout PLUS tautan manual di dalam view.
     */
    public function test_halaman_konfirmasi_hanya_punya_satu_tombol_kembali(): void
    {
        $siswa = $this->buatSiswa('pr_ganda');

        $html = $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.presensi.pindai'))
            ->get(route('siswa.presensi.konfirmasi', str_repeat('b', 64)))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'class="tombol-kembali"'),
            'Halaman memuat lebih dari satu tombol kembali.'
        );
    }

    /**
     * Setelah siswa memindai QR, scan() me-redirect ke riwayat. Referer yang
     * terlihat halaman riwayat adalah URL POST /scan/<kode>. Karena path itu juga
     * punya rute GET, dulu lolos validasi dan tombol kembali menunjuk halaman
     * konfirmasi lagi - siswa berputar tanpa pernah keluar.
     */
    public function test_setelah_scan_tombol_kembali_menuju_menu_presensi_bukan_scan_lagi(): void
    {
        $siswa = $this->buatSiswa('pr_scan');
        $kode = str_repeat('c', 64);

        $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.presensi.scan', $kode))
            ->withSession(['success' => 'Presensi berhasil dicatat.'])
            ->get(route('siswa.presensi.riwayat'))
            ->assertOk()
            ->assertSee('href="'.route('siswa.presensi.index').'"', false)
            ->assertDontSee('href="'.route('siswa.presensi.scan', $kode).'"', false);
    }

    /**
     * Referer berupa URL POST (koreksi manual, tutup sesi) harus mengarah ke
     * halaman INDUK, bukan beranda peran. Dulu guru terlempar keluar dari fitur
     * presensi setelah menyimpan koreksi.
     */
    public function test_referer_url_post_mengarah_ke_induk_halaman_bukan_beranda(): void
    {
        $guru = $this->buatGuru('pr_induk');

        $request = \Illuminate\Http\Request::create(route('guru.presensi.sesi', 5), 'GET');
        $request->headers->set('referer', route('guru.presensi.manual', 5));

        $this->assertSame(
            route('guru.presensi.index'),
            \App\Support\TujuanKembali::untuk($request, $guru),
            'Setelah menyimpan koreksi, guru harus kembali ke roster presensi, bukan ke beranda.'
        );
    }

    /**
     * Parameter rute ngawur harus berhenti sebagai 404, bukan 500.
     * Tanpa batas, "abc" memicu TypeError pada parameter bertipe int.
     */
    public function test_parameter_sesi_ngawur_menghasilkan_404_bukan_500(): void
    {
        $guru = $this->buatGuru('pr_404');

        $this->actingAs($guru)
            ->get('/guru/beranda/presensi/sesi/abc')
            ->assertNotFound();

        $this->actingAs($guru)
            ->get('/guru/beranda/presensi/sesi/999999999999999999999')
            ->assertNotFound();
    }

    /**
     * Skenario yang dilaporkan pengguna: klik "Presensi" di navbar DUA KALI.
     * Dulu tombol kembali hilang karena referer sama dengan halaman ini.
     */
    public function test_klik_menu_yang_sama_dua_kali_tetap_punya_tombol_kembali(): void
    {
        $siswa = $this->buatSiswa('pr_dua_kali');

        $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.presensi.index'))
            ->get(route('siswa.presensi.index'))
            ->assertOk()
            ->assertSee('class="tombol-kembali"', false)
            ->assertSee('href="'.route('siswa.beranda').'"', false);
    }

    /**
     * Skenario yang dilaporkan pengguna: menu presensi -> riwayat -> tekan
     * kembali (mendarat di menu). Tombol kembali di menu harus naik ke BERANDA,
     * bukan kembali ke riwayat. Dulu hasilnya bolak-balik tanpa pernah keluar.
     */
    public function test_setelah_kembali_dari_riwayat_tombol_di_menu_naik_ke_beranda(): void
    {
        $siswa = $this->buatSiswa('pr_bolak_balik');

        // Referer adalah halaman yang baru ditinggalkan (riwayat), karena itulah
        // yang dikirim browser setelah pengguna menekan tombol kembali.
        $html = $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.presensi.riwayat'))
            ->get(route('siswa.presensi.index'))
            ->assertOk()
            ->getContent();

        // Yang diperiksa HANYA href tombol kembali. Halaman menu memang berisi
        // kartu tautan ke riwayat; itu bukan tombol kembali.
        $this->assertSame(
            route('siswa.beranda'),
            $this->hrefKembali($html),
            'Tombol kembali di menu presensi harus naik ke beranda, bukan ke riwayat.'
        );
    }

    /**
     * Kasus yang sama di halaman pindai: kembali dari pindai mendarat di menu,
     * lalu tombol kembali di menu harus naik ke beranda, bukan balik ke pindai.
     */
    public function test_setelah_kembali_dari_pindai_tombol_di_menu_naik_ke_beranda(): void
    {
        $siswa = $this->buatSiswa('pr_bolak_pindai');

        $html = $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.presensi.pindai'))
            ->get(route('siswa.presensi.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            route('siswa.beranda'),
            $this->hrefKembali($html),
            'Tombol kembali di menu presensi harus naik ke beranda, bukan ke pindai.'
        );
    }

    /** Mengambil href tombol kembali dari HTML halaman. */
    private function hrefKembali(string $html): ?string
    {
        if (preg_match('/<a class="tombol-kembali" href="([^"]*)"/', $html, $m)) {
            return html_entity_decode($m[1]);
        }

        return null;
    }

    /**
     * Versi guru dari skenario yang sama: klik "Presensi" dua kali di navbar.
     */
    public function test_guru_klik_menu_presensi_dua_kali_tetap_punya_tombol_kembali(): void
    {
        $guru = $this->buatGuru('pr_guru_dua');

        $this->actingAs($guru)
            ->withHeader('referer', route('guru.presensi.index'))
            ->get(route('guru.presensi.index'))
            ->assertOk()
            ->assertSee('class="tombol-kembali"', false)
            ->assertSee('href="'.route('guru.beranda').'"', false);
    }

    /**
     * Halaman sesi guru harus naik ke roster presensi, bukan ke beranda.
     * Tanpa entri hierarki, guru terlempar keluar dari fitur presensi.
     */
    public function test_halaman_sesi_guru_kembali_ke_roster_presensi(): void
    {
        $guru = $this->buatGuru('pr_sesi_induk');

        $request = \Illuminate\Http\Request::create(route('guru.presensi.sesi', 5), 'GET');

        $this->assertSame(
            route('guru.presensi.index'),
            \App\Support\TujuanKembali::untuk($request, $guru)
        );
    }

    /**
     * Kode token lebih panjang dari VARCHAR(64) dulu memicu error 1406
     * ("Data too long for column p_kode") yang tampil sebagai 500.
     */
    public function test_kode_token_terlalu_panjang_menghasilkan_404(): void
    {
        $siswa = $this->buatSiswa('pr_token');

        $this->actingAs($siswa)
            ->get('/siswa/beranda/presensi/scan/'.str_repeat('z', 200))
            ->assertNotFound();

        $this->actingAs($siswa)
            ->get('/siswa/beranda/presensi/scan/bukanhex')
            ->assertNotFound();
    }

    /**
     * Navigasi tidak boleh membungkus <nav class="nav-links"> di dalam
     * <nav class="nav-links">. Dulu layouts/presensi dan layouts/pemilihan
     * menambah pembungkus kedua di sekitar partials.nav-utama.
     */
    public function test_navigasi_tidak_bersarang(): void
    {
        $siswa = $this->buatSiswa('pr_nav');

        $html = $this->actingAs($siswa)
            ->withHeader('referer', route('siswa.beranda'))
            ->get(route('siswa.presensi.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            '<nav class="nav-links">'."\n".'<nav class="nav-links">',
            preg_replace('/\s+/', "\n", $html),
            'Navigasi presensi membungkus <nav> di dalam <nav>.'
        );

        // Versi yang lebih longgar: jumlah pembuka dan penutup harus seimbang.
        $this->assertSame(
            substr_count($html, '<nav class="nav-links">'),
            substr_count($html, '</nav>'),
            'Jumlah <nav> dan </nav> tidak seimbang.'
        );
    }

    /**
     * Tombol "Belum" di halaman sesi guru harus tetap sama setelah 4 detik.
     *
     * Versi lama menghitung `semua anggota kelas - hadir`, sehingga siswa
     * berstatus izin/sakit/alpa ikut terhitung "belum" dan angkanya bertambah
     * sendiri tanpa ada siswa baru. qr() sekarang memakai sumber yang sama
     * dengan tampilan awal halaman.
     */
    public function test_penghitung_belum_di_qr_sama_dengan_tampilan_awal(): void
    {
        $guru = $this->buatGuru('pr_hitung');
        $idGuru = DB::table('guru')->where('id_user', $guru->id_user)->value('id_guru');

        $idKelas = DB::table('kelas')->insertGetId([
            'id_tahun_ajaran' => DB::table('tahun_ajaran')->insertGetId([
                'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30', 'is_aktif' => true,
            ]),
            'nama_kelas' => 'UJI HITUNG',
            'tingkat' => 10,
        ]);

        $idMapel = DB::table('mata_pelajaran')->insertGetId([
            'kode_mapel' => 'UJI', 'nama_mapel' => 'Uji',
        ]);

        $idJadwal = DB::table('jadwal_pelajaran')->insertGetId([
            'id_kelas' => $idKelas, 'id_mapel' => $idMapel, 'id_guru' => $idGuru,
            'semester' => 'ganjil',
            'hari' => 'senin', 'jam_mulai' => '07:00:00', 'jam_selesai' => '08:00:00',
        ]);

        // Tiga siswa: satu hadir, satu izin, satu belum tercatat.
        $idSiswa = [];
        foreach (['a', 'b', 'c'] as $i => $huruf) {
            $idUser = DB::table('users')->insertGetId([
                'username' => 'pr_hitung_'.$huruf,
                'password_hash' => Hash::make('password'),
                'role' => 'siswa', 'is_active' => true,
            ]);
            $idSiswa[] = DB::table('siswa')->insertGetId([
                'id_user' => $idUser, 'nis' => '2026'.$huruf, 'nisn' => '002026'.$huruf,
                'nama_siswa' => 'Siswa '.$huruf, 'jenis_kelamin' => 'L', 'status' => 'aktif',
            ]);
            DB::table('anggota_kelas')->insert([
                'id_kelas' => $idKelas, 'id_siswa' => end($idSiswa), 'no_absen' => $i + 1,
            ]);
        }

        $idSesi = DB::table('sesi_presensi')->insertGetId([
            'id_jadwal' => $idJadwal, 'tanggal' => now()->toDateString(),
            'dibuka_oleh' => $idGuru, 'dibuka_pada' => now(), 'status' => 'dibuka',
        ]);

        DB::table('token_presensi')->insert([
            'id_sesi' => $idSesi, 'kode_token' => str_repeat('d', 64),
            'dibuat_pada' => now(), 'kedaluwarsa_pada' => now()->addHour(),
        ]);

        DB::table('presensi')->insert([
            ['id_sesi' => $idSesi, 'id_siswa' => $idSiswa[0], 'status' => 'hadir',
             'metode' => 'barcode', 'waktu_presensi' => now()],
            ['id_sesi' => $idSesi, 'id_siswa' => $idSiswa[1], 'status' => 'izin',
             'metode' => 'manual', 'waktu_presensi' => now()],
        ]);

        $this->actingAs($guru)
            ->get(route('guru.presensi.qr', $idSesi))
            ->assertOk()
            ->assertJson([
                'status' => 'OK',
                'hadir' => 1,
                'belum' => 1,
            ]);
    }
}
