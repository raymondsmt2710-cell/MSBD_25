<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SakuSaka - SMA Negeri 1 Kabanjahe</title>
    <link rel="icon" href="{{ asset('images/logo-sakusaka-mark.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:Figtree,system-ui,-apple-system,sans-serif;background:#f6f7f8;color:#1f2937;line-height:1.5}
        a{color:inherit;text-decoration:none}


        .topbar{background:#09656F;color:#fff;padding:9px 16px;font-size:12px}
        .topbar-inner{max-width:1120px;margin:0 auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .dot{width:6px;height:6px;border-radius:999px;background:#C97A14;display:inline-block;animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}


        header.nav{position:sticky;top:0;z-index:50;background:#fff;border-bottom:1px solid #eef0f2;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .nav-inner{max-width:1120px;margin:0 auto;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px}
        .brand{display:flex;align-items:center;gap:10px}
        .brand .logo{width:36px;height:36px;border-radius:11px;background:linear-gradient(135deg,#043d44,#09656F);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0}
        .brand .nama{font-size:13px;font-weight:800;color:#09656F;line-height:1.1}
        .brand .sub{font-size:11px;color:#9ca3af;line-height:1.2}

        .nav-links{display:flex;align-items:center;gap:2px;flex-wrap:wrap}
        .nav-links a{padding:7px 11px;border-radius:10px;font-size:13px;font-weight:500;color:#4b5563}
        .nav-links a:hover{background:#f3f4f6;color:#09656F}
        .nav-aksi{display:flex;align-items:center;gap:8px}


        .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:#09656F;color:#fff;border:0;border-radius:11px;padding:9px 16px;cursor:pointer;font-size:13px;font-weight:600;font-family:inherit;transition:background .15s}
        .btn:hover{background:#07535C}
        .btn.oranye{background:#C97A14}
        .btn.oranye:hover{background:#a86410}
        .btn.garis{background:#fff;color:#09656F;border:1px solid rgba(9,101,111,.25)}
        .btn.garis:hover{background:rgba(9,101,111,.05)}
        .btn.besar{padding:12px 22px;font-size:14px;border-radius:12px}


        .hero{position:relative;background:linear-gradient(135deg,#043d44 0%,#09656F 55%,#0d7a85 100%);color:#fff;padding:64px 16px 90px;overflow:hidden}
        .hero::after{content:'';position:absolute;inset:0;background:radial-gradient(circle at 80% 20%,rgba(201,122,20,.22),transparent 55%);pointer-events:none}
        .hero-inner{max-width:1120px;margin:0 auto;position:relative;z-index:1;text-align:center}
        .lencana{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.16);padding:6px 14px;border-radius:999px;font-size:12px;font-weight:600;margin-bottom:20px}
        .hero h1{font-size:clamp(26px,4.4vw,44px);font-weight:800;line-height:1.15;letter-spacing:-.02em;margin-bottom:14px}
        .hero h1 span{color:#f0b25e}
        .hero p{font-size:clamp(13px,1.6vw,16px);color:rgba(255,255,255,.82);max-width:620px;margin:0 auto 26px;line-height:1.7}
        .hero-aksi{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        .hero-aksi .btn.garis{background:rgba(255,255,255,.1);color:#fff;border-color:rgba(255,255,255,.3)}
        .hero-aksi .btn.garis:hover{background:rgba(255,255,255,.18)}


        .statistik{max-width:960px;margin:-52px auto 0;padding:0 16px;position:relative;z-index:2}
        .statistik-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px}
        .stat-kartu{background:#fff;border:1px solid #eef0f2;border-radius:16px;padding:18px 14px;text-align:center;box-shadow:0 6px 18px rgba(16,24,40,.08)}
        .stat-kartu .ikon{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:17px}
        .stat-kartu .angka{font-size:26px;font-weight:800;line-height:1.1;color:#09656F}
        .stat-kartu .label{font-size:10px;text-transform:uppercase;letter-spacing:.09em;color:#9ca3af;font-weight:700;margin-top:4px}


        section{padding:52px 16px}
        .wrap{max-width:1120px;margin:0 auto}
        .judul-bagian{text-align:center;margin-bottom:32px}
        .judul-bagian .kicker{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#C97A14;margin-bottom:6px}
        .judul-bagian h2{font-size:clamp(20px,2.8vw,28px);font-weight:800;color:#111827;letter-spacing:-.01em}
        .judul-bagian p{font-size:13px;color:#6b7280;margin-top:8px;max-width:560px;margin-left:auto;margin-right:auto;line-height:1.7}


        .fitur-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px}
        .fitur{background:#fff;border:1px solid #eef0f2;border-radius:16px;padding:18px;transition:transform .18s,box-shadow .18s,border-color .18s}
        .fitur:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(16,24,40,.09);border-color:rgba(9,101,111,.2)}
        .fitur .ikon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:12px;background:rgba(9,101,111,.08)}
        .fitur h3{font-size:14px;font-weight:700;color:#111827;margin-bottom:6px}
        .fitur p{font-size:12px;color:#6b7280;line-height:1.65}
        .fitur .nomor{float:right;font-size:11px;font-weight:800;color:#d1d5db}


        .alur{background:#fff;border-top:1px solid #eef0f2;border-bottom:1px solid #eef0f2}
        .alur-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:22px}
        .langkah{text-align:center}
        .langkah .bulat{width:44px;height:44px;border-radius:999px;background:#09656F;color:#fff;font-weight:800;font-size:15px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px}
        .langkah h3{font-size:13px;font-weight:700;margin-bottom:5px}
        .langkah p{font-size:12px;color:#6b7280;line-height:1.6}


        .ajakan{background:linear-gradient(135deg,#043d44,#09656F);color:#fff;border-radius:20px;padding:38px 24px;text-align:center;max-width:1120px;margin:0 auto}
        .ajakan h2{font-size:clamp(19px,2.6vw,26px);font-weight:800;margin-bottom:10px}
        .ajakan p{font-size:13px;color:rgba(255,255,255,.8);margin-bottom:22px;line-height:1.7}


        footer{background:#111827;color:#9ca3af;padding:34px 16px 26px;font-size:12px}
        .footer-inner{max-width:1120px;margin:0 auto;display:flex;justify-content:space-between;gap:22px;flex-wrap:wrap}
        .footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:10px}

        .logo-footer{display:inline-flex;align-items:center;justify-content:center;background:#F9F5E9;border-radius:10px;padding:5px 8px}
        .footer-brand .logo{width:34px;height:34px;border-radius:10px;background:#09656F;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px}
        .footer h4{color:#fff;font-size:13px;font-weight:700;margin-bottom:8px}
        .footer-kolom p{line-height:1.8}
        .footer-bawah{max-width:1120px;margin:26px auto 0;padding-top:18px;border-top:1px solid rgba(255,255,255,.08);text-align:center;font-size:11px;color:#6b7280}

        @media(max-width:640px){
            .nav-links{display:none}
            .hero{padding:44px 16px 76px}
            .statistik{margin-top:-40px}
            section{padding:38px 16px}
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
        <a href="{{ url('/') }}" class="brand">
            <x-logo mark tinggi="36" />
            <div>
                <div class="nama">SakuSaka</div>
                <div class="sub">SMA Negeri 1 Kabanjahe</div>
            </div>
        </a>

        <nav class="nav-links">
            <a href="#fitur">Fitur</a>
            <a href="#cara">Cara Pakai</a>
            <a href="#tentang">Tentang</a>
        </nav>

        <div class="nav-aksi">
            <a class="btn" href="{{ route('login') }}">Masuk</a>
        </div>
    </div>
</header>

<div class="hero">
    <div class="hero-inner">
        <div class="lencana">
            <span class="dot"></span>
            Pusat Informasi &amp; Layanan Digital Sekolah
        </div>
        <h1>Satu Sistem untuk<br><span>Semua Kebutuhan Sekolah</span></h1>
        <p>
            Presensi QR, roster kelas, pemilihan elektronik, kas kelas, dan pemantauan
            kehadiran oleh orang tua — semuanya dalam satu tempat.
        </p>
        <div class="hero-aksi">
            <a class="btn besar oranye" href="{{ route('login') }}">Masuk ke Sistem</a>
            <a class="btn besar garis" href="#fitur">Lihat Fitur</a>
        </div>
    </div>
</div>

<div class="statistik">
    <div class="statistik-grid">
        <div class="stat-kartu">
            <div class="ikon" style="background:rgba(9,101,111,.1);color:#09656F"><x-ikon nama="people" ukuran="19" /></div>
            <div class="angka">{{ number_format($jumlahSiswa, 0, ',', '.') }}</div>
            <div class="label">Siswa Aktif</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon" style="background:rgba(27,94,142,.1);color:#1B5E8E"><x-ikon nama="mortarboard" ukuran="19" /></div>
            <div class="angka">{{ number_format($jumlahGuru, 0, ',', '.') }}</div>
            <div class="label">Guru</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon" style="background:rgba(201,122,20,.12);color:#C97A14"><x-ikon nama="building" ukuran="19" /></div>
            <div class="angka">{{ number_format($jumlahKelas, 0, ',', '.') }}</div>
            <div class="label">Kelas</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon" style="background:rgba(30,51,53,.1);color:#1E3335"><x-ikon nama="check2-square" ukuran="19" /></div>
            <div class="angka">{{ number_format($jumlahPemilihan, 0, ',', '.') }}</div>
            <div class="label">Pemilihan</div>
        </div>
    </div>
</div>

<section id="fitur">
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Yang Tersedia</div>
            <h2>Fitur Sistem</h2>
            <p>Tujuh layanan yang saling terhubung, dibangun di atas basis data relasional.</p>
        </div>

        <div class="fitur-grid">
            @foreach ($fitur as $f)
                <div class="fitur">
                    <span class="nomor">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <div class="ikon"><x-ikon nama="{{ $f['ikon'] }}" ukuran="20" /></div>
                    <h3>{{ $f['judul'] }}</h3>
                    <p>{{ $f['deskripsi'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="cara" class="alur">
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Langkah Mudah</div>
            <h2>Cara Menggunakan</h2>
            <p>Cukup masuk dengan akun yang diberikan sekolah.</p>
        </div>

        <div class="alur-grid">
            <div class="langkah">
                <div class="bulat">1</div>
                <h3>Masuk</h3>
                <p>Gunakan username dan password dari sekolah.</p>
            </div>
            <div class="langkah">
                <div class="bulat">2</div>
                <h3>Sesuai Peran</h3>
                <p>Sistem otomatis menampilkan menu sesuai peran Anda: siswa, guru, orang tua, atau admin.</p>
            </div>
            <div class="langkah">
                <div class="bulat">3</div>
                <h3>Gunakan Fitur</h3>
                <p>Lakukan presensi, ikuti pemilihan, atau pantau kehadiran.</p>
            </div>
        </div>
    </div>
</section>

<section id="tentang">
    <div class="ajakan">
        <h2>Siap masuk ke SakuSaka?</h2>
        <p>Gunakan akun yang telah diberikan oleh pihak sekolah untuk mengakses seluruh fitur.</p>
        <a class="btn besar oranye" href="{{ route('login') }}">Masuk Sekarang</a>
    </div>
</section>

<footer class="footer">
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
            <h4>Fitur</h4>
            <p>Presensi QR · Roster Kelas<br>Pemilihan Elektronik · Kas Kelas<br>Organisasi · Denah Kelas · Pemantauan</p>
        </div>

        <div class="footer-kolom">
            <h4>Akses</h4>
            <p>
                <a href="{{ route('login') }}" style="color:#C97A14;font-weight:600">Masuk ke Sistem →</a>
            </p>
        </div>
    </div>

    <div class="footer-bawah">
        &copy; {{ date('Y') }} SMA Negeri 1 Kabanjahe. Seluruh hak cipta dilindungi.
    </div>
</footer>

</body>
</html>
