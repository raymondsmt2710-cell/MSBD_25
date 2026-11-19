<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Tujuan tombol "kembali": halaman sebelumnya menurut riwayat browser, dengan
// pengaman supaya tombolnya tidak pernah membawa pengguna ke jalan buntu.
//
// Kasus yang dijaga (lihat spec bagian 1):
//   1. Setelah menyimpan form, Laravel me-redirect sehingga url()->previous()
//      menunjuk URL POST. Mengunjunginya dengan GET menghasilkan 405.
//   2. Membuka halaman langsung dari bookmark: tidak ada referer sama sekali.
//   3. Referer dari domain lain (tautan dibagikan lewat WhatsApp, dsb).
//   4. Setelah login, referer beranda adalah /login. Aturan pengganti mengarahkan
//      ke beranda peran — yang justru halaman yang sedang dibuka, sehingga
//      tombolnya tidak berguna. Hasil akhir dibandingkan dengan URL sekarang.
final class TujuanKembali
{
    public static function untuk(Request $request, ?User $pengguna): ?string
    {
        if ($pengguna === null) {
            return null;
        }

        $asal = $request->headers->get('referer');

        if ($asal === null || $asal === '') {
            return null;
        }

        // Host berbeda: jangan kirim pengguna ke luar aplikasi.
        //
        // Pembandingnya host REQUEST, bukan URL::to('/'): aplikasi bisa dibuka
        // lewat 127.0.0.1 maupun localhost (APP_URL hanya salah satunya).
        // Memakai APP_URL membuat tombol kembali hilang tanpa alasan.
        $hostAplikasi = $request->getHost();
        $hostAsal = parse_url($asal, PHP_URL_HOST);

        if ($hostAsal === null || ! hash_equals($hostAplikasi, $hostAsal)) {
            return null;
        }

        // Halaman yang memakai parameter navigasi pada dirinya sendiri (mis.
        // /siswa/beranda/presensi/riwayat?pekan=...) tidak boleh memakai referer:
        // hasilnya tombol bolak-balik antar pekan tanpa pernah keluar halaman.
        //
        // Hanya berlaku bila yang berbeda adalah QUERY-nya. Referer yang identik
        // dengan halaman ini (tanpa beda query) tetap dianggap tidak berguna.
        $pathAsal = parse_url($asal, PHP_URL_PATH) ?: '/';

        if ($pathAsal === $request->getPathInfo() && $asal !== $request->fullUrl()) {
            return self::induk($request, $pengguna);
        }

        $path = parse_url($asal, PHP_URL_PATH) ?: '/';
        $beranda = route($pengguna->role->home());

        // Kembali ke halaman login tidak masuk akal: pengguna sudah masuk.
        if ($path === '/' || $path === '/login') {
            return $beranda === $request->fullUrl() ? null : $beranda;
        }

        // Hanya rute GET yang sah. URL POST (mis. /akun/sandi) akan 405.
        try {
            Route::getRoutes()->match(Request::create($asal, 'GET'));
        } catch (MethodNotAllowedHttpException|NotFoundHttpException) {
            return $beranda === $request->fullUrl() ? null : $beranda;
        }

        // Referer yang sama dengan halaman ini: tombolnya tidak ada gunanya.
        return $asal === $request->fullUrl() ? null : $asal;
    }

    /**
     * Halaman induk untuk halaman yang bernavigasi pada dirinya sendiri
     * (mis. riwayat presensi dengan ?pekan=...).
     *
     * Aturannya: naik satu tingkat dari path saat ini. Bila tidak ada induk
     * yang masuk akal, kembalikan beranda peran.
     */
    private static function induk(Request $request, User $pengguna): ?string
    {
        $segmen = array_values(array_filter(explode('/', $request->getPathInfo())));

        // Buang segmen terakhir, lalu cari rute GET pertama yang cocok.
        while (count($segmen) > 1) {
            array_pop($segmen);
            $calon = '/'.implode('/', $segmen);

            try {
                Route::getRoutes()->match(Request::create($calon, 'GET'));

                $tujuan = $request->getSchemeAndHttpHost().$calon;

                return $tujuan === $request->fullUrl() ? null : $tujuan;
            } catch (MethodNotAllowedHttpException|NotFoundHttpException) {
                // lanjut naik satu tingkat lagi
            }
        }

        $beranda = route($pengguna->role->home());

        return $beranda === $request->fullUrl() ? null : $beranda;
    }
}
