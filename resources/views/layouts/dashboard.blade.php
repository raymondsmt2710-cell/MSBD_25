<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Beranda') - SakuSaka</title>
    <link rel="icon" href="{{ asset('images/logo-sakusaka-mark.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        /* Flex kolom setinggi layar, dipadukan dengan footer{margin-top:auto}:
           footer selalu menempel di dasar, juga pada halaman pendek (mis. /akun
           di ponsel). Pada halaman panjang sisa ruangnya nol, jadi tampilannya
           sama seperti sebelumnya.
           Catatan penting: isi halaman dibungkus .isi-utama, BUKAN diletakkan
           langsung di dalam body. Kalau .hero/.statistik/.kembali-wrap menjadi
           flex item, properti margin:auto mereka tidak lagi meregangkan lebar
           (flex item menyusut ke ukuran isi), sehingga kartu statistik runtuh
           menjadi satu kolom sempit dan konten berhenti di tengah layar. */
        body{font-family:Figtree,system-ui,-apple-system,sans-serif;background:#f6f7f8;color:#1f2937;line-height:1.5;display:flex;flex-direction:column;min-height:100vh}
        .isi-utama{flex:1;min-width:0}
        a{color:inherit;text-decoration:none}

        /* ---------- Bar atas ---------- */
        .topbar{background:#09656F;color:#fff;padding:9px 16px;font-size:12px}
        .topbar-inner{max-width:1120px;margin:0 auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .dot{width:6px;height:6px;border-radius:999px;background:#C97A14;display:inline-block;animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}

        /* ---------- Navigasi ---------- */
        header.nav{position:sticky;top:0;z-index:50;background:#fff;border-bottom:1px solid #eef0f2;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .nav-inner{max-width:1120px;margin:0 auto;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;position:relative}
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
        /* Tombol profil versi desktop. .nav-profil di bawah hanya tampil di ponsel,
           jadi tanpa ini halaman /akun tidak bisa dijangkau dari desktop.
           Diletakkan DI DALAM .nav-aksi supaya posisi nav-links tidak bergeser. */
        .nav-profil-dalam{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:999px;background:rgba(9,101,111,.08);color:#09656F;flex-shrink:0;transition:background .15s}
        .nav-profil-dalam:hover{background:rgba(9,101,111,.16)}

        /* ---------- Menu ponsel (hamburger, tanpa JavaScript) ----------
           <details> menangani buka-tutup sendiri, jadi tidak ada JS yang bisa gagal.
           Di desktop ketiga elemen ini disembunyikan dan nav-links yang dipakai. */
        .nav-drawer{display:none}
        .nav-hamburger{display:none}
        .nav-profil{display:none}
        .drawer-panel{display:none}

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

        /* ---------- Kartu statistik ----------
           Ponsel: satu baris mendatar yang bisa digeser (tidak pernah turun ke
           baris kedua). Desktop: kembali jadi grid seperti semula. */
        .statistik{max-width:1120px;margin:-52px auto 0;padding:0 16px;position:relative;z-index:2}
        /* overflow-x:auto membuat overflow-y ikut auto, sehingga bayangan kartu
           terpotong. Padding atas/bawah memberi ruang agar bayangan tetap utuh. */
        .statistik-grid{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;padding:2px 2px 20px;-webkit-overflow-scrolling:touch}
        /* 120px, bukan 112px: dengan padding 14px per sisi, 112px hanya menyisakan
           82px untuk angka, sedangkan "100.00%" butuh lebih dari itu. */
        .stat-kartu{flex:0 0 120px;scroll-snap-align:start;background:#fff;border:1px solid #eef0f2;border-radius:16px;padding:18px 14px;text-align:center;box-shadow:0 6px 18px rgba(16,24,40,.08)}
        .stat-kartu .ikon{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;background:rgba(9,101,111,.1);color:#09656F}
        /* nowrap: tanpa ini tanda "%" turun ke baris kedua pada angka terpanjang. */
        .stat-kartu .angka{font-size:22px;font-weight:800;line-height:1.1;color:#09656F;white-space:nowrap}
        .stat-kartu .label{font-size:10px;text-transform:uppercase;letter-spacing:.09em;color:#9ca3af;font-weight:700;margin-top:4px}
        /* auto-fit, bukan repeat(5,1fr): dashboard guru hanya punya 4 kartu. */
        @media(min-width:641px){
            .statistik-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));overflow:visible;padding:0}
            .stat-kartu{flex:none}
            .stat-kartu .angka{font-size:26px}
        }

        /* ---------- Bagian umum ---------- */
        section{padding:44px 16px}
        .wrap{max-width:1120px;margin:0 auto}
        .judul-bagian{margin-bottom:22px}
        .judul-bagian .kicker{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#C97A14;margin-bottom:6px}
        .judul-bagian h2{font-size:clamp(19px,2.6vw,26px);font-weight:800;color:#111827;letter-spacing:-.01em}
        .judul-bagian p{font-size:13px;color:#6b7280;margin-top:6px;line-height:1.7}

        /* ---------- Tombol kembali ----------
           Dipakai semua layout. Di dashboard .kembali-wrap butuh lebar maksimum
           sendiri karena isi halaman berada langsung di dalam body. */
        .kembali-wrap{max-width:1120px;margin:0 auto;padding:16px 16px 0}
        .tombol-kembali{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;background:rgba(9,101,111,.07);color:#09656F;font-size:12px;font-weight:600;transition:background .15s}
        .tombol-kembali:hover{background:rgba(9,101,111,.14)}

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

        /* ---------- Form ---------- */
        .form-baris{margin-bottom:16px}
        .form-label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:#6b7280;margin-bottom:6px}
        .form-input{width:100%;border:1px solid #e5e7eb;border-radius:11px;padding:10px 12px;font-size:14px;font-family:inherit;color:#1f2937;background:#fff;transition:border-color .15s,box-shadow .15s}
        .form-input:focus{outline:0;border-color:#09656F;box-shadow:0 0 0 3px rgba(9,101,111,.12)}
        .form-input-salah{border-color:#dc2626}
        .form-error{font-size:12px;color:#dc2626;margin-top:5px}
        .form-aksi{display:flex;gap:8px;flex-wrap:wrap;margin-top:4px}
        .notif{border-radius:12px;padding:11px 14px;font-size:13px;margin-bottom:16px;border:1px solid transparent}
        .notif-sukses{background:rgba(9,101,111,.08);border-color:rgba(9,101,111,.2);color:#09656F;font-weight:600}
        .notif-gagal{background:rgba(220,38,38,.07);border-color:rgba(220,38,38,.22);color:#b91c1c;font-weight:600}

        /* ---------- Footer ---------- */
        /* margin-top:auto mendorong footer ke dasar layar. Nilai 20px lama
           diganti karena auto sudah mencakup jarak pada halaman panjang. */
        footer{background:#111827;color:#9ca3af;padding:34px 16px 26px;font-size:12px;margin-top:auto}
        /* Logo asli berlatar krem; di footer gelap diberi alas krem agar tetap terbaca. */
        .logo-footer{display:inline-flex;align-items:center;justify-content:center;background:#F9F5E9;border-radius:10px;padding:5px 8px}
        .footer-inner{max-width:1120px;margin:0 auto;display:flex;justify-content:space-between;gap:22px;flex-wrap:wrap}
        .footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:10px}
        .footer-brand .logo{width:34px;height:34px;border-radius:10px;background:#09656F;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px}
        .footer-kolom h4{color:#fff;font-size:13px;font-weight:700;margin-bottom:8px}
        .footer-kolom p{line-height:1.8}
        .footer-bawah{max-width:1120px;margin:26px auto 0;padding-top:18px;border-top:1px solid rgba(255,255,255,.08);text-align:center;font-size:11px;color:#6b7280}

        @media(max-width:900px){
            /* Di lebar menengah nav-links masih tampil, tetapi tombol profil +
               Keluar sudah memakan ruang. Nama pengguna disembunyikan lebih dulu
               supaya bar tidak membungkus ke dua baris. */
            .nav-pengguna{display:none}
        }

        @media(max-width:640px){
            /* Tautan nav digantikan drawer; logo dipusatkan; profil di kanan. */
            .nav-links{display:none}
            .nav-pengguna{display:none}
            .nav-aksi{display:none}
            .nav-drawer{display:block}
            .nav-hamburger{display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:#f3f4f6;color:#09656F;cursor:pointer;list-style:none;flex-shrink:0}
            .nav-hamburger::-webkit-details-marker{display:none}
            .nav-hamburger::marker{content:''}
            .nav-profil{display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:999px;background:rgba(9,101,111,.08);color:#09656F;flex-shrink:0}
            .brand{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);gap:8px}
            .brand .sub{display:none}
            /* Panel membentang selebar bar, di bawahnya, bukan menempel di tombol. */
            .drawer-panel{display:block;position:absolute;left:0;right:0;top:100%;background:#fff;border-top:1px solid #eef0f2;box-shadow:0 14px 26px rgba(16,24,40,.14);padding:14px 16px 18px;z-index:60}
            /* Batasi tinggi: pada layar pendek (320x568) empat kartu tetap bisa
               digulir, tidak terpotong di luar viewport. */
            .drawer-panel{max-height:calc(100vh - 64px);overflow-y:auto;-webkit-overflow-scrolling:touch}
            .drawer-kepala{display:flex;align-items:center;gap:9px;padding-bottom:11px;margin-bottom:11px;border-bottom:1px solid #eef0f2}
            .drawer-kepala .logo{width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#043d44,#09656F);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:11px;flex-shrink:0}
            .drawer-kepala .nama{font-size:13px;font-weight:800;color:#09656F;line-height:1.1}
            .drawer-kepala .sub{font-size:10px;color:#9ca3af;line-height:1.2}
            /* Kartu ringkas bertumpuk ke bawah: satu baris per tujuan, bukan grid.
               Tanpa baris keterangan, jadi tingginya cukup satu baris. */
            .drawer-menu{display:flex;flex-direction:column;gap:6px}
            .drawer-item{display:flex;align-items:center;gap:9px;padding:8px 10px;border:1px solid #eef0f2;border-radius:10px;background:#fff;transition:background .15s,border-color .15s}
            .drawer-item:active{background:rgba(9,101,111,.05);border-color:rgba(9,101,111,.2)}
            .drawer-item .ikon{width:26px;height:26px;border-radius:8px;background:rgba(9,101,111,.09);color:#09656F;display:flex;align-items:center;justify-content:center;flex-shrink:0}
            .drawer-item .judul{font-size:12.5px;font-weight:700;color:#111827;line-height:1.2}
            /* Halaman /akun: dua kolom masih terbaca, satu kolom di ponsel sempit. */
            .data-grid{grid-template-columns:1fr}
            .hero{padding:40px 16px 72px}
            .statistik{margin-top:-42px}
            section{padding:32px 16px}
            .footer-inner{flex-direction:column}
        }
    </style>
    @include('partials.css-nav-tengah')
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
        {{-- Kiri (ponsel saja): hamburger. Isinya section menu-cepat milik tiap
             peran, sehingga tidak pernah ada tombol yang membuka panel kosong. --}}
        @hasSection('menu-cepat')
            <details class="nav-drawer">
                <summary class="nav-hamburger" aria-label="Buka menu cepat">
                    <x-ikon nama="list" ukuran="20" />
                </summary>
                <div class="drawer-panel">
                    <div class="drawer-kepala">
                        <x-logo mark tinggi="30" />
                        <div>
                            <div class="nama">SakuSaka</div>
                            <div class="sub">SMA Negeri 1 Kabanjahe</div>
                        </div>
                    </div>
                    @yield('menu-cepat')
                </div>
            </details>
        @endif

        <a href="@yield('brand-href', url('/'))" class="brand">
            <x-logo mark tinggi="36" />
            <div>
                <div class="nama">SakuSaka</div>
                <div class="sub">SMA Negeri 1 Kabanjahe</div>
            </div>
        </a>

        @yield('nav')

        {{-- Kanan (ponsel saja): tombol profil menuju halaman ganti sandi. --}}
        <a href="{{ route('akun.profil') }}" class="nav-profil" aria-label="Profil dan ganti password">
            <x-ikon nama="person-circle" ukuran="22" />
        </a>

        <div class="nav-aksi">
            <span class="nav-pengguna">{{ auth()->user()->username ?? '' }}</span>
            <a href="{{ route('akun.profil') }}" class="nav-profil-dalam" aria-label="Profil dan ganti password">
                <x-ikon nama="person-circle" ukuran="20" />
            </a>
        </div>
    </div>
</header>

{{-- Beranda peran tidak menampilkan tombol kembali: beranda adalah puncak
     navigasi, jadi tidak ada "halaman sebelumnya" yang berguna. Halaman turunan
     yang memakai layout ini (/akun, /siswa/perangkat) tetap memilikinya. --}}
<div class="isi-utama">
    @unless (request()->routeIs('siswa.beranda', 'guru.beranda'))
        <x-tombol-kembali />
    @endunless

    @yield('isi')
</div>

<footer>
    <div class="footer-inner">
        <div class="footer-kolom" style="max-width:320px">
            <div class="footer-brand">
                <span class="logo-footer">
                    <x-logo mark tinggi="26" />
                </span>
                <div>
                    <div style="color:#fff;font-weight:800;font-size:13px">SakuSaka</div>
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
