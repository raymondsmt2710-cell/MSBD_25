<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function form()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $kredensial = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($kredensial + ['is_active' => true])) {
            $request->session()->regenerate();

            DB::table('users')->where('id_user', Auth::id())->update(['last_login' => now()]);

            return redirect()->intended($this->beranda(Auth::user()->role));
        }

        return back()->withErrors(['username' => 'Username atau password salah.'])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function beranda(string $role): string
    {
        return match ($role) {
            'guru'      => route('guru.presensi.index'),
            'siswa'     => route('siswa.presensi.riwayat'),
            'admin'     => route('kelola.pemilihan.index'),
            'orang_tua' => route('ortu.index'),
            default     => route('beranda'),
        };
    }
}
