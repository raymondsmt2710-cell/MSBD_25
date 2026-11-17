@extends('layouts.dashboard')

@section('brand-href', route('guru.beranda'))

{{-- Guru tidak punya akses voting, jadi "Pemilihan Elektronik" tidak disebut
     di footer halaman guru. --}}
@section('akses-cepat')
    Presensi &middot; Roster<br>Perangkat Kelas &amp; Organisasi
@endsection

@section('nav')
    <nav class="nav-links">
        <a href="{{ route('guru.beranda') }}"
           class="{{ request()->routeIs('guru.beranda') ? 'aktif' : '' }}">Beranda</a>
        <a href="{{ route('guru.presensi.index') }}"
           class="{{ request()->routeIs('guru.presensi.*') ? 'aktif' : '' }}">Presensi</a>
    </nav>
@endsection
