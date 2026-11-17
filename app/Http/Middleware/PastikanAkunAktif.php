<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Mencabut akses akun yang dinonaktifkan (users.is_active = 0) pada setiap request.
//
// Login sudah memeriksa is_active, tetapi itu hanya terjadi saat login. Tanpa middleware
// ini, session yang sudah terbuka tetap berjalan penuh setelah admin menonaktifkan akun,
// sehingga "nonaktifkan akun" tidak benar-benar mencabut akses.
class PastikanAkunAktif
{
    // Rute peralihan identitas dilewati: pada POST /login, user di session masih user LAMA,
    // sehingga memeriksa is_active di sini akan memblokir login akun baru hanya karena
    // session lama milik akun yang sudah dinonaktifkan.
    private const RUTE_DILEWATI = ['login', 'login.proses', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->ruteDilewati($request)) {
            return $next($request);
        }

        $user = $request->user();

        // Nilai dibaca langsung dari model (atribut asli), bukan dari cache session.
        if ($user !== null && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Akun Anda sudah dinonaktifkan. Hubungi administrator.');
        }

        return $next($request);
    }

    private function ruteDilewati(Request $request): bool
    {
        $rute = $request->route();

        if (! $rute instanceof \Illuminate\Routing\Route) {
            return false;
        }

        $nama = $rute->getName();

        return $nama !== null && in_array($nama, self::RUTE_DILEWATI, true);
    }
}
