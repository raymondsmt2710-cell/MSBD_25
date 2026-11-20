{{--
    Layout untuk fitur Kas Kelas & Denah (port dari branch fitur ke desain main).
    Gaya, palet, dan komponennya sama dengan resources/views/layouts/pemilihan.blade.php
    yang kini menjadi layout utama aplikasi versi main.
    Dipakai:  <x-app-layout judul="Kas Kelas"> ... </x-app-layout>
--}}
@props(['judul' => null])

@php
    $pengguna = auth()->user();

    // app-layout dipakai halaman kas & denah. Navigasinya sengaja tetap memuat
    // tautan fitur tersebut (termasuk untuk siswa) karena halaman ini memang
    // halaman fitur itu; menunya berbeda dari nav dashboard biasa.
    $peran = $pengguna?->role?->value ?? (string) $pengguna?->role;

    $navigasi = match ($peran) {
        // Admin sudah punya sidebar lengkap di halaman ini, jadi nav atas
        // dibiarkan kosong agar tidak ada tombol dobel.
        'admin' => [],
        'guru' => [
            ['label' => 'Beranda', 'route' => 'guru.beranda', 'aktif' => 'guru.beranda'],
            ['label' => 'Presensi', 'route' => 'guru.presensi.index', 'aktif' => 'guru.presensi.*'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index', 'aktif' => 'kas.*'],
            ['label' => 'Denah', 'route' => 'denah.index', 'aktif' => 'denah.*'],
        ],
        'siswa' => [
            ['label' => 'Beranda', 'route' => 'siswa.beranda', 'aktif' => 'siswa.beranda'],
            ['label' => 'Presensi', 'route' => 'siswa.presensi.index', 'aktif' => 'siswa.presensi.*'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index', 'aktif' => 'kas.*'],
            ['label' => 'Denah', 'route' => 'denah.index', 'aktif' => 'denah.*'],
        ],
        'orang_tua' => [
            ['label' => 'Portal Orang Tua', 'route' => 'ortu.index', 'aktif' => 'ortu.*'],
        ],
        default => [],
    };
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $judul ? $judul.' - ' : '' }}SakuSaka - SMA Negeri 1 Kabanjahe</title>
    <link rel="icon" href="{{ asset('images/logo-sakusaka-mark.png') }}" type="image/png">

    {{-- Alpine.js + modul halaman (kasPage, denahPage). Tanpa ini seluruh isi
         yang memakai x-data/x-cloak tidak akan pernah tampil. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        /* Hanya kerangka layout. Styling isi halaman diserahkan ke Tailwind
           (app.css) supaya view Kas & Denah tampil sesuai kelas utilitasnya. */
        body{margin:0;font-family:Figtree,system-ui,-apple-system,sans-serif;background:#f6f7f8;color:#1f2937}
        .topbar{background:#09656F;color:#fff;padding:10px 16px;font-size:12px}
        .topbar-inner{max-width:1120px;margin:0 auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .dot{width:6px;height:6px;border-radius:999px;background:#C97A14;display:inline-block}
        header.nav{position:sticky;top:0;z-index:50;background:#fff;border-bottom:1px solid #eef0f2;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .nav-inner{max-width:1120px;margin:0 auto;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px}
        .brand{display:flex;align-items:center;gap:10px}
        .brand .logo{width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#043d44,#09656F);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px}
        .brand .nama{font-size:13px;font-weight:800;color:#09656F;line-height:1.1}
        .brand .sub{font-size:11px;color:#9ca3af;line-height:1.2}
        .nav-links{display:flex;align-items:center;gap:2px;flex-wrap:wrap}
        .nav-links a{padding:7px 11px;border-radius:10px;font-size:13px;font-weight:500;color:#4b5563;text-decoration:none}
        .nav-links a:hover{background:#f3f4f6;color:#09656F}
        .nav-links a.aktif{background:rgba(9,101,111,.06);color:#09656F}
        .nav-aksi{display:flex;align-items:center;gap:8px}
        .nav-profil{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:999px;background:rgba(9,101,111,.08);color:#09656F;flex-shrink:0;transition:background .15s}
        .nav-profil:hover{background:rgba(9,101,111,.16)}
        .nav-aksi .pill{display:inline-flex;align-items:center;gap:5px;background:#f3f4f6;border-radius:999px;padding:4px 10px;font-size:11px;color:#4b5563;font-weight:600}
        .nav-aksi .btn-keluar{background:#6b7280;color:#fff;border:0;border-radius:10px;padding:8px 14px;cursor:pointer;font-size:13px;font-weight:600;font-family:inherit}
        .nav-aksi .btn-keluar:hover{background:#4b5563}
        .isi-layout{max-width:1120px;margin:0 auto;padding:22px 16px 60px}
        .kembali-wrap{margin-bottom:14px}
        .tombol-kembali{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;background:rgba(9,101,111,.07);color:#09656F;font-size:12px;font-weight:600;transition:background .15s}
        .tombol-kembali:hover{background:rgba(9,101,111,.14)}
        @media(max-width:640px){
            .nav-links{display:none}
        }
    </style>
    @include('partials.css-nav-tengah')
</head>
@php
    // Admin memakai sidebar di SEMUA halaman, termasuk halaman kas & denah.
    $peranAktif = auth()->user()?->role;
    $peranAktif = $peranAktif instanceof \App\Enums\Role ? $peranAktif->value : (string) $peranAktif;
    $pakaiSidebarAdmin = $peranAktif === 'admin';
@endphp
@if ($pakaiSidebarAdmin)
    @include('partials.css-sidebar-admin')
@endif
<body>
@if ($pakaiSidebarAdmin)
<div class="kerangka-admin">
    @include('partials.sidebar-admin')
    <div class="isi-admin">
@endif
<div class="topbar">
    <div class="topbar-inner">
        <span class="dot"></span>
        <strong>{{ $judul ?? 'SakuSaka' }}</strong>
        <span style="opacity:.75">SMA Negeri 1 Kabanjahe</span>
    </div>
</div>

<header class="nav">
    <div class="nav-inner">
        <a href="{{ url('/') }}" class="brand">
            <x-logo mark tinggi="34" />
            <div>
                <div class="nama">SakuSaka</div>
                <div class="sub">SMA Negeri 1 Kabanjahe</div>
            </div>
        </a>

        <nav class="nav-links">
            @auth
                @foreach ($navigasi as $item)
                    <a href="{{ route($item['route']) }}"
                       class="{{ request()->routeIs($item['aktif']) ? 'aktif' : '' }}">{{ $item['label'] }}</a>
                @endforeach
            @endauth
        </nav>

        <div class="nav-aksi">
            @auth
                <a href="{{ route('akun.profil') }}" class="nav-profil" aria-label="Profil dan ganti password">
                    <x-ikon nama="person-circle" ukuran="20" />
                </a>
                <span class="pill">{{ $pengguna->username }} · {{ $pengguna->role?->value ?? $pengguna->role }}</span>
            @else
                <a class="btn-keluar" href="{{ route('login') }}">Masuk</a>
            @endauth
        </div>
    </div>
</header>

<main class="isi-layout">
    <x-tombol-kembali />

    {{-- Slot header: judul halaman dari pemanggil (mis. "Kas Kelas" di kas/index).
         Tanpa ini judul halaman tidak pernah tampil. --}}
    @if (isset($header))
        <div class="mb-4">
            {{ $header }}
        </div>
    @endif

    @if (session('success'))<div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">
            @foreach ($errors->all() as $pesan)
                <div>{{ $pesan }}</div>
            @endforeach
        </div>
    @endif

    {{ $slot }}
</main>
@if ($pakaiSidebarAdmin)
    </div>{{-- .isi-admin --}}
</div>{{-- .kerangka-admin --}}
@endif
@include('partials.script-sidebar-admin')
</body>
</html>
