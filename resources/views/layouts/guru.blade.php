@extends('layouts.dashboard')

@section('brand-href', route('guru.beranda'))

@section('akses-cepat')
    Presensi &middot; Roster<br>Perangkat Kelas &amp; Organisasi
@endsection

@section('nav')
    @include('partials.nav-utama', ['gaya' => 'nav'])
@endsection

@section('menu-cepat')
    @include('partials.menu-cepat-guru')
@endsection
