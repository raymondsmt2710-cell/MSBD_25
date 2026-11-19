@extends('layouts.dashboard')

@section('brand-href', route('siswa.beranda'))

{{-- Nav horizontal memakai sumber bersama supaya "Beranda" selalu ada di
     halaman mana pun. Drawer ponsel tetap memakai menu-cepat-siswa yang
     kartunya menunjuk ke anchor bagian halaman. --}}
@section('nav')
    @include('partials.nav-utama', ['gaya' => 'nav'])
@endsection

@section('menu-cepat')
    @include('partials.menu-cepat-siswa')
@endsection
