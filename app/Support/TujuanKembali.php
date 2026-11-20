<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Tujuan tombol "kembali".
//
// Aturan utama: naik SATU tingkat dalam hierarki halaman, bukan mengikuti riwayat
// browser apa adanya.
//
// Kenapa bukan riwayat browser: riwayat hanya tahu halaman yang barusan dibuka.
// Setelah pengguna menekan "kembali" sekali, halaman yang barusan dibuka adalah
// halaman yang baru saja ia tinggalkan. Referer berikutnya menunjuk ke sana, jadi
// tombol kembali membawanya maju lagi - bolak-balik tanpa pernah naik. Contoh
// nyata yang dilaporkan pengguna:
//
//   menu presensi -> riwayat -> [kembali] -> menu presensi (benar)
//   tetapi tombol kembali di menu presensi sekarang menunjuk ke riwayat (salah)
//
// Hal yang sama terjadi saat pengguna mengklik menu yang sedang dibuka (mis.
// menekan "Presensi" dua kali di navbar): referer sama dengan halaman ini dan
// tombolnya hilang sama sekali.
//
// Karena itu hierarki ditulis eksplisit di HIERARKI. Referer hanya dipakai bila
// mengarah ke halaman yang masih satu cabang dan bukan leluhur dari halaman
// sekarang (lihat bolehPakaiReferer).
final class TujuanKembali
{
    /**
     * Peta hierarki halaman: path anak => path induk.
     *
     * Ditulis tangan, bukan ditebak dari segmen URL, karena dua alasan:
     *
     *   1. Presensi berada di bawah beranda peran (/siswa/beranda/presensi),
     *      sehingga induk dari /siswa/beranda/presensi/riwayat menurut segmen
     *      adalah /siswa/beranda/presensi - benar. Tetapi induk dari
     *      /siswa/beranda/presensi menurut segmen adalah /siswa/beranda - juga
     *      benar. Yang tidak bisa ditebak adalah bahwa /siswa/beranda/presensi
     *      harus naik ke beranda, sedangkan /siswa/beranda/presensi/riwayat
     *      harus naik ke menu presensi. Peta ini menyatakannya eksplisit.
     *
     *   2. Halaman yang tidak terdaftar tetap ditangani: naik satu segmen bila
     *      hasilnya rute GET yang sah, lalu beranda peran sebagai jaring pengaman.
     *
     * Kunci memakai path TANPA nama rute karena request hanya memberi path.
     * route('...') dipakai di sisi nilai supaya perubahan URL tidak perlu
     * mengubah peta ini.
     */
    private const HIERARKI = [
        // ---- Presensi siswa ----
        '/siswa/beranda/presensi' => 'siswa.beranda',
        '/siswa/beranda/presensi/riwayat' => 'siswa.presensi.index',
        '/siswa/beranda/presensi/pindai' => 'siswa.presensi.index',
        '/siswa/beranda/presensi/scan' => 'siswa.presensi.pindai',

        // ---- Presensi guru ----
        '/guru/beranda/presensi' => 'guru.beranda',
        '/guru/beranda/presensi/sesi' => 'guru.presensi.index',

        // ---- Pemilihan siswa ----
        '/siswa/pemilihan' => 'siswa.beranda',
        '/siswa/pemilihan/pilih' => 'siswa.pemilihan.index',
        '/siswa/pemilihan/hasil' => 'siswa.pemilihan.index',

        // ---- Kelola pemilihan (admin & guru) ----
        '/kelola/pemilihan' => 'admin.dashboard',
        '/kelola/pemilihan/baru' => 'kelola.pemilihan.index',
        '/kelola/pemilihan/ubah' => 'kelola.pemilihan.index',
        '/kelola/pemilihan/kandidat' => 'kelola.pemilihan.index',
        '/kelola/pemilihan/pemilih' => 'kelola.pemilihan.index',
        '/kelola/pemilihan/panitia' => 'kelola.pemilihan.index',
        '/kelola/pemilihan/monitor' => 'kelola.pemilihan.index',
        '/kelola/pemilihan/hasil' => 'kelola.pemilihan.index',
        '/kelola/pemilihan/log' => 'kelola.pemilihan.index',

        // ---- Perangkat ----
        '/perangkat' => null, // diisi saat runtime: beranda peran
        '/perangkat/kelas' => 'perangkat.index',
        '/perangkat/organisasi' => 'perangkat.index',
        '/kelola/perangkat' => null,
        '/kelola/perangkat/kelas' => 'perangkat.kelola.index',
        '/kelola/perangkat/organisasi' => 'perangkat.kelola.index',

        // ---- Kas ----
        '/kas' => null,
        '/kas/data' => 'kas.index',
        '/kas/export' => 'kas.index',
        '/kas/cetak' => 'kas.index',

        // ---- Denah ----
        '/denah' => null,

        // ---- Portal orang tua ----
        '/ortu' => null,
        '/ortu/anak' => 'ortu.index',

        // ---- Akun ----
        '/akun' => null,
    ];

    /**
     * Induk dari sebuah path, atau null bila tidak ada yang masuk akal.
     *
     * @param  string  $path  path tanpa query string
     */
    private static function indukDariPath(string $path, User $pengguna): ?string
    {
        $path = rtrim($path, '/') ?: '/';

        // 1. Peta eksplisit. Cocokkan awalan terpanjang supaya
        //    /guru/beranda/presensi/sesi/5 memakai entri /guru/beranda/presensi/sesi.
        $cocok = null;

        foreach (self::HIERARKI as $pola => $namaRute) {
            if ($path === $pola || str_starts_with($path, $pola.'/')) {
                if ($cocok === null || strlen($pola) > strlen($cocok)) {
                    $cocok = $pola;
                }
            }
        }

        if ($cocok !== null) {
            $namaRute = self::HIERARKI[$cocok];

            // null berarti "beranda peran" - halaman puncak fitur itu.
            return $namaRute === null ? route($pengguna->role->home()) : route($namaRute);
        }

        // 2. Tidak terdaftar: naik satu segmen bila hasilnya rute GET yang sah.
        $segmen = array_values(array_filter(explode('/', $path)));

        while (count($segmen) > 1) {
            array_pop($segmen);
            $calon = '/'.implode('/', $segmen);

            if (self::ruteGetSah($calon)) {
                return url($calon);
            }
        }

        // 3. Jaring pengaman.
        return route($pengguna->role->home());
    }

    /** Apakah path ini bisa dibuka dengan GET? */
    private static function ruteGetSah(string $path): bool
    {
        try {
            Route::getRoutes()->match(Request::create($path, 'GET'));

            return true;
        } catch (MethodNotAllowedHttpException|NotFoundHttpException) {
            return false;
        }
    }

    public static function untuk(Request $request, ?User $pengguna): ?string
    {
        if ($pengguna === null) {
            return null;
        }

        $sekarang = $request->getPathInfo() ?: '/';
        $beranda = route($pengguna->role->home());

        // Beranda adalah puncak navigasi: tidak ada tempat untuk kembali.
        if (self::samaPath($sekarang, $beranda) && $request->fullUrl() === $beranda) {
            return null;
        }

        $asal = $request->headers->get('referer');

        // Tanpa referer (bookmark, ketik URL langsung): tetap beri jalan pulang
        // satu tingkat, bukan menghilangkan tombolnya.
        if ($asal === null || $asal === '') {
            return self::tujuanDariInduk($request, $pengguna);
        }

        // Host berbeda: jangan kirim pengguna ke luar aplikasi.
        //
        // Pembandingnya host REQUEST, bukan APP_URL: aplikasi bisa dibuka lewat
        // 127.0.0.1 maupun localhost, dan APP_URL hanya salah satunya.
        $hostAsal = parse_url($asal, PHP_URL_HOST);

        if ($hostAsal === null || ! hash_equals($request->getHost(), $hostAsal)) {
            return self::tujuanDariInduk($request, $pengguna);
        }

        $pathAsal = parse_url($asal, PHP_URL_PATH) ?: '/';

        // Referer dari halaman login: pengguna sudah masuk, jadi tidak berguna.
        if ($pathAsal === '/' || $pathAsal === '/login') {
            return self::tujuanDariInduk($request, $pengguna);
        }

        // Referer yang tidak bisa dibuka dengan GET (URL POST, mis. setelah
        // menyimpan form). Membukanya menghasilkan 405.
        if (! self::ruteGetSah($pathAsal)) {
            return self::tujuanDariInduk($request, $pengguna);
        }

        // Referer sama dengan halaman ini: pengguna menekan menu halaman yang
        // sedang dibuka (mis. klik "Presensi" dua kali di navbar). Naik satu
        // tingkat, jangan hilangkan tombolnya.
        if (self::samaPath($pathAsal, $sekarang)) {
            return self::tujuanDariInduk($request, $pengguna);
        }

        // Referer yang berada di CABANG YANG SAMA dengan halaman ini tidak bisa
        // dipakai: pengguna baru saja menekan "kembali" atau mengklik menu
        // halaman yang sedang dibuka.
        //
        // Dua kemungkinan, keduanya berarti "naik satu tingkat":
        //   a. Referer adalah LELUHUR halaman ini
        //      (riwayat -> menu presensi). Ini terjadi setelah pengguna menekan
        //      kembali dari riwayat ke menu.
        //   b. Referer adalah ANAK halaman ini
        //      (menu presensi <- riwayat). Ini juga terjadi setelah pengguna
        //      menekan kembali: halaman yang baru ditinggalkan adalah anaknya.
        //
        // Memakai referer pada kedua kasus membuat tombol kembali membawa
        // pengguna MAJU lagi, sehingga bolak-balik tanpa pernah naik.
        //
        // Catatan gabungan: kasus "referer sama dengan halaman ini setelah form
        // disimpan" (back() mengembalikan ke halaman yang sama) sudah tertangani
        // oleh pemeriksaan samaPath() di atas, dan lebih luas - samaPath()
        // membandingkan path saja, sehingga referer yang hanya beda query pun
        // ikut tertangani.
        if (self::satuCabang($pathAsal, $sekarang, $pengguna)) {
            return self::tujuanDariInduk($request, $pengguna);
        }

        // Pengguna baru saja diarahkan ke sini setelah sebuah aksi (POST).
        //
        // Penandanya adalah pesan hasil aksi (success/error) yang masih menempel
        // di session. Contoh nyata: siswa selesai memindai QR, lalu mendarat di
        // riwayat dengan referer /siswa/beranda/presensi/scan/<kode>.
        //
        // Path /scan/<kode> PUNYA rute GET (halaman konfirmasi) dan bukan satu
        // cabang dengan riwayat, sehingga dua pemeriksaan di atas meloloskannya.
        // Tanpa aturan ini, tombol kembali membawa siswa ke halaman konfirmasi
        // LAGI - berputar tanpa pernah keluar.
        if (self::baruSajaBeraksi($request)) {
            return self::tujuanDariInduk($request, $pengguna);
        }

        // Referer adalah halaman lain yang sah dan bukan satu cabang: itu memang
        // halaman sebelumnya, pakai apa adanya.
        return $asal;
    }

    /**
     * Apakah request ini hasil redirect setelah sebuah aksi (POST)?
     *
     * Penandanya adalah pesan hasil aksi (success/error) yang masih menempel di
     * session. Redirect setelah aksi selalu menyertakan salah satunya, dan justru
     * pada kondisi itulah referer tidak berguna sebagai tujuan kembali.
     */
    private static function baruSajaBeraksi(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $sesi = $request->session();

        return $sesi->has('success') || $sesi->has('error');
    }

    /**
     * Apakah dua path berada di satu cabang hierarki (salah satu leluhur dari
     * yang lain, atau keduanya halaman yang sama)?
     */
    private static function satuCabang(string $a, string $b, User $pengguna): bool
    {
        return self::leluhurDari($a, $b, $pengguna)
            || self::leluhurDari($b, $a, $pengguna);
    }

    /**
     * Tujuan "naik satu tingkat", dengan penjagaan agar tidak menunjuk halaman
     * yang sedang dibuka.
     */
    private static function tujuanDariInduk(Request $request, User $pengguna): ?string
    {
        $induk = self::indukDariPath($request->getPathInfo() ?: '/', $pengguna);

        if ($induk === null) {
            return null;
        }

        // Jangan pernah menawarkan tombol yang menuju halaman ini sendiri.
        if (self::samaPath(parse_url($induk, PHP_URL_PATH) ?: '/', $request->getPathInfo() ?: '/')) {
            return null;
        }

        return $induk;
    }

    /** Apakah dua path menunjuk halaman yang sama (tanpa peduli garis miring akhir)? */
    private static function samaPath(string $a, string $b): bool
    {
        return rtrim($a, '/') === rtrim($b, '/');
    }

    /**
     * Apakah $leluhur adalah induk (langsung atau tidak) dari $anak menurut peta
     * hierarki?
     *
     * Dipakai untuk mendeteksi "pengguna baru menekan kembali". Contoh:
     * halaman sekarang /siswa/beranda/presensi/riwayat, referer
     * /siswa/beranda/presensi. Induk riwayat memang menu presensi, jadi referer
     * itu adalah leluhur - tombol kembali harus naik ke beranda, bukan kembali
     * ke riwayat.
     */
    private static function leluhurDari(string $leluhur, string $anak, User $pengguna): bool
    {
        $sekarang = rtrim($anak, '/');
        $batas = 12; // pencegah putaran tak terbatas bila peta salah

        while ($batas-- > 0) {
            $induk = self::indukDariPath($sekarang, $pengguna);

            if ($induk === null) {
                return false;
            }

            $pathInduk = rtrim(parse_url($induk, PHP_URL_PATH) ?: '/', '/');

            if ($pathInduk === rtrim($leluhur, '/')) {
                return true;
            }

            if ($pathInduk === $sekarang) {
                return false;
            }

            $sekarang = $pathInduk;
        }

        return false;
    }
}
