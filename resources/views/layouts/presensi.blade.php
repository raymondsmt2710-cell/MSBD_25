<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Presensi') — SI-SMANSAKA</title>
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
        main{max-width:1120px;margin:0 auto;padding:22px 16px 60px}
        h1{font-size:22px;margin:0 0 4px}
        .subjudul{font-size:13px;color:#6b7280;margin:0 0 18px}
        .kartu{background:#fff;border:1px solid #eef0f2;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(16,24,40,.05);margin-bottom:16px}
        .kartu h2,.kartu h3{margin:0 0 12px;font-size:15px}
        .ok{background:#dcfce7;color:#166534;padding:11px 14px;border-radius:12px;margin-bottom:14px;font-size:13px}
        .err{background:#fee2e2;color:#991b1b;padding:11px 14px;border-radius:12px;margin-bottom:14px;font-size:13px}
        .info{background:#eff6ff;color:#1e40af;padding:11px 14px;border-radius:12px;margin-bottom:14px;font-size:12.5px;line-height:1.6}
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
        .badge.hadir{background:#dcfce7;color:#166534}
        .badge.izin,.badge.sakit{background:#fef9c3;color:#854d0e}
        .badge.alpa{background:#fee2e2;color:#991b1b}
        .badge.belum{background:#e5e7eb;color:#374151}
        .grid{display:grid;gap:14px;align-items:start}
        .grid-2{grid-template-columns:repeat(auto-fit,minmax(260px,1fr))}
        .stat{display:flex;flex-direction:column;gap:2px}
        .stat .angka{font-size:22px;font-weight:800;color:#09656F;line-height:1.1}
        .stat .label{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;font-weight:700}
        .pill{display:inline-flex;align-items:center;gap:5px;background:#f3f4f6;border-radius:999px;padding:4px 10px;font-size:11px;color:#4b5563;font-weight:600}
        .kosong{text-align:center;padding:34px 16px;color:#9ca3af;font-size:13px}
        .aksi-baris{display:flex;gap:6px;flex-wrap:wrap}
        .aksi-baris form{display:inline}
        .kecil{font-size:11px;color:#9ca3af}

        /* ---------- Dua tombol pilihan di halaman presensi ---------- */
        .pilihan{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px}
        .pilihan-kartu{display:flex;flex-direction:column;gap:10px;padding:20px;border:1px solid #eef0f2;border-radius:16px;background:#fff;transition:transform .18s,box-shadow .18s,border-color .18s}
        .pilihan-kartu:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(16,24,40,.09);border-color:rgba(9,101,111,.28)}
        .pilihan-ikon{width:44px;height:44px;border-radius:13px;display:flex;align-items:center;justify-content:center;background:rgba(9,101,111,.08);color:#09656F}
        .pilihan-kartu.oranye .pilihan-ikon{background:rgba(201,122,20,.12);color:#C97A14}
        .pilihan-judul{font-size:15px;font-weight:800;margin:0}
        .pilihan-ket{font-size:12.5px;color:#6b7280;line-height:1.6;margin:0}

        /* ---------- Area pemindai QR ---------- */
        .pemindai{position:relative;background:#043d44;border-radius:16px;overflow:hidden;min-height:280px;display:flex;align-items:center;justify-content:center}
        #pembaca{width:100%}
        #pembaca video{width:100%!important;height:auto!important;border-radius:16px;display:block}
        .pemindai-kosong{color:rgba(255,255,255,.72);font-size:13px;text-align:center;padding:36px 20px;line-height:1.7}
        .bingkai{position:absolute;inset:0;pointer-events:none;display:none}
        .bingkai.tampil{display:block}
        .bingkai::before,.bingkai::after{content:'';position:absolute;width:38px;height:38px;border:3px solid #f0b25e;border-radius:6px}
        .bingkai::before{top:16%;left:16%;border-right:0;border-bottom:0}
        .bingkai::after{bottom:16%;right:16%;border-left:0;border-top:0}
        .garis-pindai{position:absolute;left:16%;right:16%;height:2px;background:linear-gradient(90deg,transparent,#f0b25e,transparent);animation:pindai 2.2s ease-in-out infinite}
        @keyframes pindai{0%,100%{top:18%}50%{top:80%}}
        .pemisah{display:flex;align-items:center;gap:12px;margin:16px 0;color:#9ca3af;font-size:11px;text-transform:uppercase;letter-spacing:.1em;font-weight:700}
        .pemisah::before,.pemisah::after{content:'';flex:1;height:1px;background:#eef0f2}

        @media(max-width:640px){
            .nav-links{display:none}
            h1{font-size:19px}
            th,td{padding:8px 6px;font-size:12px}
        }
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-inner">
        <span class="dot"></span>
        <strong>Presensi QR</strong>
        <span style="opacity:.75">SMA Negeri 1 Kabanjahe — kehadiran tercatat otomatis</span>
    </div>
</div>

<header class="nav">
    <div class="nav-inner">
        <a href="{{ auth()->check() ? route('dashboard.presensi.index') : url('/') }}" class="brand">
            <div class="logo">SM</div>
            <div>
                <div class="nama">SI-SMANSAKA</div>
                <div class="sub">SMA Negeri 1 Kabanjahe</div>
            </div>
        </a>

        <nav class="nav-links">
            @auth
                @if (auth()->user()->role === 'siswa')
                    <a href="{{ route('dashboard.presensi.index') }}"
                       class="{{ request()->routeIs('dashboard.presensi.index') ? 'aktif' : '' }}">Presensi</a>
                @elseif (auth()->user()->role === 'guru')
                    <a href="{{ route('dashboard.presensi.index') }}"
                       class="{{ request()->routeIs('dashboard.presensi.*') ? 'aktif' : '' }}">Roster Presensi</a>
                @elseif (auth()->user()->role === 'orang_tua')
                    <a href="{{ route('ortu.index') }}">Portal Orang Tua</a>
                @endif
            @endauth
        </nav>

        <div class="nav-aksi">
            @auth
                <span class="pill">{{ auth()->user()->username }} · {{ auth()->user()->role }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn abu" type="submit">Keluar</button>
                </form>
            @else
                <a class="btn" href="{{ route('login') }}">Masuk</a>
            @endauth
        </div>
    </div>
</header>

<main>
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

@stack('skrip')
</body>
</html>
