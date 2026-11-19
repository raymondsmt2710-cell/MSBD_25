@extends('layouts.dashboard')

@section('brand-href', route('guru.beranda'))

{{-- Guru tidak punya akses voting, jadi "Pemilihan Elektronik" tidak disebut
     di footer halaman guru. --}}
@section('akses-cepat')
    Presensi &middot; Roster<br>Perangkat Kelas &amp; Organisasi
@endsection

{{-- Nav horizontal memakai sumber bersama (partials.nav-utama); daftar guru di
     sana sengaja tanpa "Pemilihan". Drawer tetap memakai menu-cepat-guru. --}}
@section('nav')
    @include('partials.nav-utama', ['gaya' => 'nav'])
@endsection

@section('menu-cepat')
    @include('partials.menu-cepat-guru')
@endsection
