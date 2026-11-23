@extends('layouts.dashboard')

@section('brand-href', route('siswa.beranda'))

@section('nav')
    @include('partials.nav-utama', ['gaya' => 'nav'])
@endsection

@section('menu-cepat')
    @include('partials.menu-cepat-siswa')
@endsection
