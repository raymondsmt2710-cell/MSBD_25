@extends('layouts.dashboard')

{{-- Halaman akun dipakai semua peran (rute hanya `auth`), jadi brand dan isi
     drawer harus mengikuti peran pengguna. Memakai Role::home() agar tidak ada
     peran yang diarahkan ke beranda milik peran lain (yang akan 403).
     Catatan: jangan menaruh @section('judul') di sini — judul milik halaman anak,
     dan section inline di layout perantara akan menimpanya (lihat
     LayoutSiswaRegresiTest). --}}
@section('brand-href', route(auth()->user()->role->home()))

{{-- Drawer hamburger memuat kartu Menu Cepat peran pengguna, supaya tidak ada
     tujuan yang hilang. Admin dan orang tua tidak punya kartu Menu Cepat, jadi
     @hasSection di layout dashboard otomatis tidak menampilkan hamburger. --}}
@section('menu-cepat')
    @if (auth()->user()->role === \App\Enums\Role::Guru)
        @include('partials.menu-cepat-guru')
    @elseif (auth()->user()->role === \App\Enums\Role::Siswa)
        @include('partials.menu-cepat-siswa')
    @endif
@endsection

{{-- Footer mengikuti peran: guru tidak boleh ditawari Pemilihan Elektronik. --}}
@section('akses-cepat')
    @if (auth()->user()->role === \App\Enums\Role::Guru)
        Profil &middot; Ganti Sandi<br>Presensi &middot; Roster
    @elseif (auth()->user()->role === \App\Enums\Role::Siswa)
        Profil &middot; Ganti Sandi<br>Presensi &middot; Perangkat Kelas<br>Pemilihan Elektronik
    @else
        Profil &middot; Ganti Sandi
    @endif
@endsection
