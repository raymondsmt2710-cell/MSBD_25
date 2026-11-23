@extends('layouts.dashboard')

@section('brand-href', route(auth()->user()->role->home()))

@section('menu-cepat')
    @if (auth()->user()->role === \App\Enums\Role::Guru)
        @include('partials.menu-cepat-guru')
    @elseif (auth()->user()->role === \App\Enums\Role::Siswa)
        @include('partials.menu-cepat-siswa')
    @endif
@endsection

@section('akses-cepat')
    @if (auth()->user()->role === \App\Enums\Role::Guru)
        Profil &middot; Ganti Sandi<br>Presensi &middot; Roster
    @elseif (auth()->user()->role === \App\Enums\Role::Siswa)
        Profil &middot; Ganti Sandi<br>Presensi &middot; Perangkat Kelas<br>Pemilihan Elektronik
    @else
        Profil &middot; Ganti Sandi
    @endif
@endsection
