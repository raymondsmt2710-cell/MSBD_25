<?php

namespace App\Http\Controllers;

use App\Enums\Role;
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

    private function beranda(Role|string $role): string
    {
        $role = $role instanceof Role ? $role : Role::tryFrom($role);

        return match ($role) {
            Role::Guru => route('guru.presensi.index'),
            Role::Siswa => route('siswa.presensi.riwayat'),
            default => route('beranda'),
        };
    }
}
