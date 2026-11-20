<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Pemilihan') - SakuSaka</title>
    <link rel="icon" href="{{ asset('images/logo-sakusaka-mark.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:Figtree,system-ui,-apple-system,sans-serif;background:#f6f7f8;color:#1f2937}
        a{color:inherit;text-decoration:none}
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
        .nav-links a{padding:7px 11px;border-radius:10px;font-size:13px;font-weight:500;color:#4b5563}
        .nav-links a:hover{background:#f3f4f6;color:#09656F}
        .nav-links a.aktif{background:rgba(9,101,111,.06);color:#09656F}
        .nav-aksi{display:flex;align-items:center;gap:8px}
        /* Tombol profil ke /akun. Layout ini tidak punya hamburger, jadi tombolnya
           tampil di semua lebar; nama pengguna disembunyikan di ponsel agar muat. */
        .nav-profil{display:flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:999px;background:rgba(9,101,111,.08);color:#09656F;flex-shrink:0}
        .nav-profil:hover{background:rgba(9,101,111,.15)}
        .kembali-wrap{margin-bottom:14px}
        .tombol-kembali{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;background:rgba(9,101,111,.07);color:#09656F;font-size:12px;font-weight:600;transition:background .15s}
        .tombol-kembali:hover{background:rgba(9,101,111,.14)}
        main{max-width:1120px;margin:0 auto;padding:22px 16px 60px}
        h1{font-size:22px;margin:0 0 4px}
        .subjudul{font-size:13px;color:#6b7280;margin:0 0 18px}
        .kartu{background:#fff;border:1px solid #eef0f2;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(16,24,40,.05);margin-bottom:16px}
        .kartu h2,.kartu h3{margin:0 0 12px;font-size:15px}
        .ok{background:#dcfce7;color:#166534;padding:11px 14px;border-radius:12px;margin-bottom:14px;font-size:13px}
        .err{background:#fee2e2;color:#991b1b;padding:11px 14px;border-radius:12px;margin-bottom:14px;font-size:13px}
        .btn{display:inline-flex;align-items:center;gap:6px;background:#09656F;color:#fff;border:0;border-radius:11px;padding:9px 15px;cursor:pointer;font-size:13px;font-weight:600;font-family:inherit}
        .btn:hover{background:#07535C}
        .btn.oranye{background:#C97A14}.btn.oranye:hover{background:#a86410}
        .btn.merah{background:#b91c1c}.btn.merah:hover{background:#991b1b}
        .btn.abu{background:#6b7280}.btn.abu:hover{background:#4b5563}
        .btn.garis{background:#fff;color:#09656F;border:1px solid rgba(9,101,111,.25)}
        .btn.garis:hover{background:rgba(9,101,111,.05)}
        .btn:disabled{opacity:.5;cursor:not-allowed}
        table{width:100%;border-collapse:collapse}
        th,td{padding:10px 8px;border-bottom:1px solid #f1f2f4;text-align:left;font-size:13px;vertical-align:middle}
        th{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;font-weight:700}
        input,select,textarea{padding:9px 11px;border:1px solid #dfe2e6;border-radius:10px;font-size:13px;font-family:inherit;width:100%;background:#fff}
        input:focus,select:focus,textarea:focus{outline:none;border-color:#09656F;box-shadow:0 0 0 3px rgba(9,101,111,.12)}
        label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px}
        .badge{display:inline-block;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700}
        .badge.draf{background:#e5e7eb;color:#374151}
        .badge.berlangsung{background:#dcfce7;color:#166534}
        .badge.selesai{background:#dbeafe;color:#1e40af}
        /* Status presensi — dipakai portal orang tua (fitur 7). Fitur pemilihan tidak
           memakai kelas ini, jadi penambahannya tidak mengubah tampilan mana pun. */
        .badge.hadir{background:#dcfce7;color:#166534}
        .badge.izin,.badge.sakit{background:#fef9c3;color:#854d0e}
        .badge.alpa{background:#fee2e2;color:#991b1b}
        .grid{display:grid;gap:14px;align-items:start}
        .grid-3{grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}
        .grid-2{grid-template-columns:repeat(auto-fit,minmax(260px,1fr))}
        .kandidat-kartu{background:#fff;border:1px solid #eef0f2;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;transition:transform .18s,box-shadow .18s}
        .kandidat-kartu:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(16,24,40,.09)}
        .kandidat-foto{height:170px;flex-shrink:0;background:linear-gradient(135deg,#043d44,#09656F);display:flex;align-items:center;justify-content:center;color:#fff;font-size:38px;font-weight:800;overflow:hidden}
        .kandidat-foto img{width:100%;height:100%;object-fit:cover}
        .kandidat-isi{padding:14px;display:flex;flex-direction:column;gap:8px;flex:1}
        /* Tombol aksi selalu menempel di dasar kartu, sehingga kartu dengan
           teks lebih pendek tetap terlihat sejajar tanpa memaksa tinggi sama. */
        .kandidat-isi .btn{width:100%;justify-content:center;margin-top:auto}
        /* Detail kandidat: ringkasan yang bisa dibuka, bukan <details> polos,
           supaya teksnya tidak bertabrakan dengan visi di atasnya. */
        .detail-kandidat{margin-top:2px}
        .detail-kandidat summary{list-style:none;cursor:pointer;display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#09656F;padding:4px 0;user-select:none}
        .detail-kandidat summary::-webkit-details-marker{display:none}
        .detail-kandidat summary::before{content:'▸';font-size:11px;transition:transform .15s}
        .detail-kandidat[open] summary::before{transform:rotate(90deg)}
        .detail-kandidat summary:hover{color:#07535C;text-decoration:underline}
        .detail-isi{margin-top:8px;padding:10px 12px;background:#f8f9fa;border-radius:10px;font-size:12px;color:#4b5563;line-height:1.65;display:flex;flex-direction:column;gap:6px}
        .detail-isi strong{color:#374151}
        .nomor{display:inline-block;background:#C97A14;color:#fff;font-size:11px;font-weight:800;padding:3px 9px;border-radius:999px;letter-spacing:.04em}
        .nama-pasangan{font-weight:800;font-size:14px;line-height:1.3}
        .visi{font-size:12px;color:#6b7280;line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
        .bar{height:9px;background:#eef0f2;border-radius:999px;overflow:hidden}
        .bar span{display:block;height:100%;background:linear-gradient(90deg,#09656F,#0d8b96);border-radius:999px}
        .bar.menang span{background:linear-gradient(90deg,#C97A14,#e0932a)}
        .meta{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:12px;color:#6b7280}
        .stat{display:flex;flex-direction:column;gap:2px}
        .stat .angka{font-size:20px;font-weight:800;color:#09656F;line-height:1.1}
        .stat .label{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;font-weight:700}
        .pill{display:inline-flex;align-items:center;gap:5px;background:#f3f4f6;border-radius:999px;padding:4px 10px;font-size:11px;color:#4b5563;font-weight:600}
        .modal-latar{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:16px;z-index:100}
        .modal-latar.buka{display:flex}
        .modal{background:#fff;border-radius:18px;max-width:420px;width:100%;padding:22px;box-shadow:0 24px 60px rgba(15,23,42,.35)}
        .modal h3{margin:0 0 8px;font-size:16px}
        .modal p{margin:0 0 18px;font-size:13px;color:#4b5563;line-height:1.6}
        .modal-aksi{display:flex;gap:9px;justify-content:flex-end}
        .kosong{text-align:center;padding:34px 16px;color:#9ca3af;font-size:13px}
        .paginasi{margin-top:14px}
        .paginasi nav{display:flex;gap:4px;flex-wrap:wrap}
        .paginasi a,.paginasi span{padding:6px 10px;border-radius:8px;font-size:12px;border:1px solid #e5e7eb;color:#4b5563;background:#fff}
        .paginasi span[aria-current]{background:#09656F;color:#fff;border-color:#09656F}
        .aksi-baris{display:flex;gap:6px;flex-wrap:wrap}
        .aksi-baris form{display:inline}
        .kecil{font-size:11px;color:#9ca3af}
        @media(max-width:640px){
            .nav-links{display:none}
            /* Nama pengguna + peran memakan ruang; tombol profil & Keluar tetap. */
            .nav-aksi .pill{display:none}
            h1{font-size:19px}
            th,td{padding:8px 6px;font-size:12px}
            .nav-hamburger{display:inline-flex}
        }

        /* ---------- Hamburger + drawer (ponsel) ---------- */
        .nav-hamburger{display:none;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;background:rgba(9,101,111,.08);color:#09656F;cursor:pointer;list-style:none;flex-shrink:0}
        .nav-hamburger::-webkit-details-marker{display:none}
        .nav-drawer{display:none}
        .drawer-panel{display:none}
        @media(max-width:640px){
            .nav-drawer{display:block}
            .drawer-panel{display:block;position:absolute;left:0;right:0;top:100%;background:#fff;border-bottom:1px solid #eef0f2;box-shadow:0 12px 24px rgba(16,24,40,.1);padding:12px 16px 16px;z-index:60;max-height:calc(100vh - 64px);overflow-y:auto}
            .drawer-menu{display:flex;flex-direction:column;gap:6px}
            .drawer-item{display:flex;align-items:center;gap:9px;padding:9px 11px;border:1px solid #eef0f2;border-radius:11px;font-size:13px;font-weight:600;color:#374151}
            .drawer-item:hover{background:#f7f8f9;border-color:rgba(9,101,111,.2)}
            .drawer-item.aktif{background:rgba(9,101,111,.08);color:#09656F;border-color:rgba(9,101,111,.25)}
            .nav-inner{position:relative}
        }
    </style>
</head>
@php
    // Admin memakai sidebar di SEMUA halaman, termasuk yang memakai layout ini.
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
        @auth
            @if ((auth()->user()->role?->value ?? auth()->user()->role) === 'orang_tua')
                <strong>Portal Orang Tua</strong>
                <span style="opacity:.75">SMA Negeri 1 Kabanjahe — pemantauan wali murid</span>
            @else
                <strong>Pemilihan Elektronik</strong>
                <span style="opacity:.75">SMA Negeri 1 Kabanjahe — suara Anda rahasia</span>
            @endif
        @else
            <strong>Pemilihan Elektronik</strong>
            <span style="opacity:.75">SMA Negeri 1 Kabanjahe — suara Anda rahasia</span>
        @endauth
    </div>
</div>

<header class="nav">
    <div class="nav-inner">
        {{-- Logo mengarah ke BERANDA PERAN, sama seperti layout presensi. Dulu
             menunjuk url('/') sehingga admin/guru/siswa yang menekannya keluar
             dari area perannya (dan siswa mendarat di halaman tamu). --}}
        <a href="{{ auth()->check() ? route(auth()->user()->role->home()) : url('/') }}" class="brand">
            <x-logo mark tinggi="34" />
            <div>
                <div class="nama">SakuSaka</div>
                <div class="sub">SMA Negeri 1 Kabanjahe</div>
            </div>
        </a>

        {{-- partials.nav-utama sudah membungkus daftar tautan dengan
             <nav class="nav-links">. Jangan menambah pembungkus kedua di sini. --}}
        @auth
            @include('partials.nav-utama', ['gaya' => 'nav'])
        @endauth

        {{-- Hamburger: di ponsel tautan nav disembunyikan, jadi menu pindah ke sini. --}}
        @auth
            <details class="nav-drawer">
                <summary class="nav-hamburger" aria-label="Buka menu">
                    <x-ikon nama="list" ukuran="20" />
                </summary>
                <div class="drawer-panel">
                    @include('partials.drawer-peran')
                </div>
            </details>
        @endauth

        <div class="nav-aksi">
            @auth
                <a href="{{ route('akun.profil') }}" class="nav-profil" aria-label="Profil dan ganti password">
                    <x-ikon nama="person-circle" ukuran="20" />
                </a>
                <span class="pill">{{ auth()->user()->username }} · {{ auth()->user()->role?->value ?? auth()->user()->role }}</span>
            @else
                <a class="btn" href="{{ route('login') }}">Masuk</a>
            @endauth
        </div>
    </div>
</header>

<main>
    <x-tombol-kembali />

    @if (session('success'))<div class="ok">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="err">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="err">
            @foreach ($errors->all() as $pesan)
                <div>{{ $pesan }}</div>
            @endforeach
        </div>
    @endif

    @yield('isi')
</main>
@if ($pakaiSidebarAdmin)
    </div>{{-- .isi-admin --}}
</div>{{-- .kerangka-admin --}}
@endif
@include('partials.script-sidebar-admin')
</body>
</html>
