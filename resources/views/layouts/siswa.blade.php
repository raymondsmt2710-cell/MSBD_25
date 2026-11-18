@extends('layouts.dashboard')

@section('brand-href', route('siswa.beranda'))

@section('nav')
    <nav class="nav-links">
        <a href="{{ route('siswa.beranda') }}"
           class="{{ request()->routeIs('siswa.beranda') ? 'aktif' : '' }}">Beranda</a>
        <a href="{{ route('dashboard.presensi.riwayat') }}"
           class="{{ request()->routeIs('dashboard.presensi.*') ? 'aktif' : '' }}">Presensi</a>
        <a href="{{ route('siswa.perangkat') }}"
           class="{{ request()->routeIs('siswa.perangkat') ? 'aktif' : '' }}">Perangkat</a>
        <a href="{{ route('siswa.pemilihan.index') }}"
           class="{{ request()->routeIs('siswa.pemilihan.*') ? 'aktif' : '' }}">Pemilihan</a>
    </nav>
@endsection

{{-- Isi drawer hamburger di ponsel. Tautan nav di atas digantikan panel ini,
     jadi tujuannya sama: empat kartu Menu Cepat. --}}
@section('menu-cepat')
    @include('partials.menu-cepat-siswa')
@endsection
