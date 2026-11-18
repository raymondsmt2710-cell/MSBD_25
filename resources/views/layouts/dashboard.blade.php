<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Beranda') — SI-SMANSAKA</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:Figtree,system-ui,-apple-system,sans-serif;background:#f6f7f8;color:#1f2937;line-height:1.5}
        a{color:inherit;text-decoration:none}

        /* ---------- Bar atas ---------- */
        .topbar{background:#09656F;color:#fff;padding:9px 16px;font-size:12px}
        .topbar-inner{max-width:1120px;margin:0 auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .dot{width:6px;height:6px;border-radius:999px;background:#C97A14;display:inline-block;animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}

        /* ---------- Navigasi ---------- */
        header.nav{position:sticky;top:0;z-index:50;background:#fff;border-bottom:1px solid #eef0f2;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .nav-inner{max-width:1120px;margin:0 auto;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px}
        .brand{display:flex;align-items:center;gap:10px}
        .brand .logo{width:36px;height:36px;border-radius:11px;background:linear-gradient(135deg,#043d44,#09656F);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0}
        .brand .nama{font-size:13px;font-weight:800;color:#09656F;line-height:1.1}
        .brand .sub{font-size:11px;color:#9ca3af;line-height:1.2}
        .nav-links{display:flex;align-items:center;gap:2px;flex-wrap:wrap}
        .nav-links a{padding:7px 11px;border-radius:10px;font-size:13px;font-weight:500;color:#4b5563;transition:background .15s,color .15s}
        .nav-links a:hover{background:#f3f4f6;color:#09656F}
        .nav-links a.aktif{background:rgba(9,101,111,.08);color:#09656F;font-weight:700}
        .nav-aksi{display:flex;align-items:center;gap:8px}
        .nav-pengguna{font-size:12px;color:#6b7280}

        /* ---------- Tombol ---------- */
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:#09656F;color:#fff;border:0;border-radius:11px;padding:9px 16px;cursor:pointer;font-size:13px;font-weight:600;font-family:inherit;transition:background .15s}
        .btn:hover{background:#07535C}
        .btn.oranye{background:#C97A14}
        .btn.oranye:hover{background:#a86410}
        .btn.garis{background:#fff;color:#09656F;border:1px solid rgba(9,101,111,.25)}
        .btn.garis:hover{background:rgba(9,101,111,.05)}
        .btn.besar{padding:12px 22px;font-size:14px;border-radius:12px}

        /* ---------- Hero ---------- */
        .hero{position:relative;background:linear-gradient(135deg,#043d44 0%,#09656F 55%,#0d7a85 100%);color:#fff;padding:56px 16px 84px;overflow:hidden}
        .hero::after{content:'';position:absolute;inset:0;background:radial-gradient(circle at 80% 20%,rgba(201,122,20,.22),transparent 55%);pointer-events:none}
        .hero-inner{max-width:1120px;margin:0 auto;position:relative;z-index:1}
        .lencana{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.16);padding:6px 14px;border-radius:999px;font-size:12px;font-weight:600;margin-bottom:18px}
        .hero h1{font-size:clamp(24px,3.6vw,38px);font-weight:800;line-height:1.15;letter-spacing:-.02em;margin-bottom:12px}
        .hero h1 span{color:#f0b25e}
        .hero p{font-size:clamp(13px,1.6vw,15px);color:rgba(255,255,255,.82);line-height:1.7}
        .hero p .pemisah{opacity:.45;margin:0 8px}

        /* ---------- Kartu statistik ---------- */
        .statistik{max-width:1120px;margin:-52px auto 0;padding:0 16px;position:relative;z-index:2}
        .statistik-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px}
        .stat-kartu{background:#fff;border:1px solid #eef0f2;border-radius:16px;padding:18px 14px;text-align:center;box-shadow:0 6px 18px rgba(16,24,40,.08)}
        .stat-kartu .ikon{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;background:rgba(9,101,111,.1);color:#09656F}
        .stat-kartu .angka{font-size:26px;font-weight:800;line-height:1.1;color:#09656F}
        .stat-kartu .label{font-size:10px;text-transform:uppercase;letter-spacing:.09em;color:#9ca3af;font-weight:700;margin-top:4px}

        /* ---------- Bagian umum ---------- */
        section{padding:44px 16px}
        .wrap{max-width:1120px;margin:0 auto}
        .judul-bagian{margin-bottom:22px}
        .judul-bagian .kicker{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#C97A14;margin-bottom:6px}
        .judul-bagian h2{font-size:clamp(19px,2.6vw,26px);font-weight:800;color:#111827;letter-spacing:-.01em}
        .judul-bagian p{font-size:13px;color:#6b7280;margin-top:6px;line-height:1.7}

        /* ---------- Kartu ikon ---------- */
        .ikon-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
        .ikon-kartu{display:block;background:#fff;border:1px solid #eef0f2;border-radius:16px;padding:18px;transition:transform .18s,box-shadow .18s,border-color .18s}
        .ikon-kartu:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(16,24,40,.09);border-color:rgba(9,101,111,.2)}
        .ikon-kartu .ikon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;background:rgba(9,101,111,.08);color:#09656F}
        .ikon-kartu h3{font-size:14px;font-weight:700;color:#111827;margin-bottom:6px}
        .ikon-kartu p{font-size:12px;color:#6b7280;line-height:1.65}

        /* ---------- Kartu data ---------- */
        .kartu{background:#fff;border:1px solid #eef0f2;border-radius:16px;padding:20px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .data-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
        .data-item .kunci{font-size:10px;text-transform:uppercase;letter-spacing:.09em;color:#9ca3af;font-weight:700;margin-bottom:4px}
        .data-item .nilai{font-size:13px;color:#1f2937;font-weight:500;word-break:break-word}
        .pill{display:inline-block;background:rgba(9,101,111,.08);color:#09656F;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:700}

        /* ---------- Tabel ---------- */
        .tabel{width:100%;border-collapse:collapse}
        .tabel th{text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.09em;color:#9ca3af;font-weight:700;padding:9px 8px;border-bottom:1px solid #eef0f2}
        .tabel td{font-size:13px;padding:10px 8px;border-bottom:1px solid #f6f7f8;color:#1f2937}
        .tabel tr:last-child td{border-bottom:0}
        .kosong{font-size:13px;color:#6b7280;line-height:1.7;margin:0}

        /* ---------- Baris aksi (jadwal hari ini) ---------- */
        .aksi-baris{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px 0;border-bottom:1px solid #f6f7f8}
        .aksi-baris:last-child{border-bottom:0;padding-bottom:0}
        .aksi-baris:first-child{padding-top:0}
        .aksi-jam{font-size:13px;font-weight:800;color:#09656F}
        .aksi-judul{font-size:12px;color:#6b7280;margin-top:2px}
        .aksi-tombol{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .status-pil{display:inline-block;background:rgba(201,122,20,.12);color:#C97A14;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:700;text-transform:capitalize}

        /* ---------- Footer ---------- */
        footer{background:#111827;color:#9ca3af;padding:34px 16px 26px;font-size:12px;margin-top:20px}
        .footer-inner{max-width:1120px;margin:0 auto;display:flex;justify-content:space-between;gap:22px;flex-wrap:wrap}
        .footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:10px}
        .footer-brand .logo{width:34px;height:34px;border-radius:10px;background:#09656F;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px}
        .footer-kolom h4{color:#fff;font-size:13px;font-weight:700;margin-bottom:8px}
        .footer-kolom p{line-height:1.8}
        .footer-bawah{max-width:1120px;margin:26px auto 0;padding-top:18px;border-top:1px solid rgba(255,255,255,.08);text-align:center;font-size:11px;color:#6b7280}

        @media(max-width:640px){
            .nav-links{display:none}
            .nav-pengguna{display:none}
            .hero{padding:40px 16px 72px}
            .statistik{margin-top:-42px}
            section{padding:32px 16px}
            .footer-inner{flex-direction:column}
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <span class="dot"></span>
        <strong>Sistem Informasi Sekolah</strong>
        <span style="opacity:.75">SMA Negeri 1 Kabanjahe</span>
    </div>
</div>

<header class="nav">
    <div class="nav-inner">
        <a href="@yield('brand-href', url('/'))" class="brand">
            <div class="logo">SM</div>
            <div>
                <div class="nama">SI-SMANSAKA</div>
                <div class="sub">SMA Negeri 1 Kabanjahe</div>
            </div>
        </a>

        @yield('nav')

        <div class="nav-aksi">
            <span class="nav-pengguna">{{ auth()->user()->username ?? '' }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn garis" type="submit">
                    <x-ikon nama="box-arrow-in-right" ukuran="15" /> Keluar
                </button>
            </form>
        </div>
    </div>
</header>

@yield('isi')

<footer>
    <div class="footer-inner">
        <div class="footer-kolom" style="max-width:320px">
            <div class="footer-brand">
                <div class="logo">SM</div>
                <div>
                    <div style="color:#fff;font-weight:800;font-size:13px">SI-SMANSAKA</div>
                    <div style="font-size:11px">SMA Negeri 1 Kabanjahe</div>
                </div>
            </div>
            <p style="line-height:1.8">
                Pusat informasi dan layanan digital sekolah. Dibangun untuk memudahkan
                siswa, guru, orang tua, dan pengelola sekolah.
            </p>
        </div>

        <div class="footer-kolom">
            <h4>Akses Cepat</h4>
            {{-- Tiap peran boleh menimpa daftar ini lewat @section('akses-cepat')
                 (bentuk blok, karena bentuk inline di-escape oleh Blade). --}}
            @hasSection('akses-cepat')
                <p>@yield('akses-cepat')</p>
            @else
                <p>Presensi · Roster<br>Perangkat Kelas &amp; Organisasi<br>Pemilihan Elektronik</p>
            @endif
        </div>
    </div>

    <div class="footer-bawah">
        &copy; {{ date('Y') }} SMA Negeri 1 Kabanjahe. Seluruh hak cipta dilindungi.
    </div>
</footer>

</body>
</html>
