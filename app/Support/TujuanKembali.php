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
        if (parse_url($asal, PHP_URL_HOST) !== parse_url(URL::to('/'), PHP_URL_HOST)) {
            return null;
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
}
