<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PastikanAkunAktif
{



    private const RUTE_DILEWATI = ['login', 'login.proses', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->ruteDilewati($request)) {
            return $next($request);
        }

        $user = $request->user();


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
