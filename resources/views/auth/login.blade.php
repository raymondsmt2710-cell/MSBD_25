<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - SakuSaka</title>
    <link rel="icon" href="{{ asset('images/logo-sakusaka-mark.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:Figtree,system-ui,-apple-system,sans-serif;background:#f6f7f8;color:#1f2937;min-height:100vh;display:flex;flex-direction:column}
        a{color:inherit;text-decoration:none}


        .topbar{background:#09656F;color:#fff;padding:9px 16px;font-size:12px}
        .topbar-inner{max-width:1120px;margin:0 auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .dot{width:6px;height:6px;border-radius:999px;background:#C97A14;display:inline-block}


        .kerangka{flex:1;display:flex;align-items:center;justify-content:center;padding:32px 16px}
        .kotak{width:100%;max-width:900px;background:#fff;border:1px solid #eef0f2;border-radius:20px;overflow:hidden;display:grid;grid-template-columns:1fr 1fr;box-shadow:0 18px 44px rgba(16,24,40,.1)}


        .panel-brand{position:relative;background:linear-gradient(150deg,#043d44 0%,#09656F 58%,#0d7a85 100%);color:#fff;padding:38px 32px;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden}
        .panel-brand::after{content:'';position:absolute;inset:0;background:radial-gradient(circle at 85% 12%,rgba(201,122,20,.25),transparent 58%);pointer-events:none}
        .panel-brand > *{position:relative;z-index:1}
        .brand{display:flex;align-items:center;gap:10px;margin-bottom:30px}
        .brand .logo{width:40px;height:40px;border-radius:12px;background:#F9F5E9;border:1px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;flex-shrink:0}
        .brand .nama{font-size:14px;font-weight:800;line-height:1.15}
        .brand .sub{font-size:11px;color:rgba(255,255,255,.65);line-height:1.2}
        .panel-brand h2{font-size:23px;font-weight:800;line-height:1.25;margin-bottom:10px;letter-spacing:-.01em}
        .panel-brand h2 span{color:#f0b25e}
        .panel-brand .ket{font-size:13px;color:rgba(255,255,255,.8);line-height:1.7;margin-bottom:26px}
        .daftar-fitur{display:flex;flex-direction:column;gap:11px}
        .baris-fitur{display:flex;align-items:flex-start;gap:10px;font-size:12.5px;color:rgba(255,255,255,.88);line-height:1.5}
        .baris-fitur .tanda{width:18px;height:18px;border-radius:999px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0;margin-top:1px}


        .panel-form{padding:38px 32px;display:flex;flex-direction:column;justify-content:center}
        .panel-form h1{font-size:21px;font-weight:800;margin-bottom:5px;letter-spacing:-.01em}
        .panel-form .subjudul{font-size:13px;color:#6b7280;margin-bottom:24px}

        .grup{margin-bottom:16px}
        label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px}
        .input-wrap{position:relative}
        .input-wrap .ikon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none;display:flex;align-items:center}
        input{width:100%;padding:11px 12px 11px 36px;border:1px solid #dfe2e6;border-radius:11px;font-size:13.5px;font-family:inherit;background:#fff;color:#1f2937;transition:border-color .15s,box-shadow .15s}
        input:focus{outline:none;border-color:#09656F;box-shadow:0 0 0 3px rgba(9,101,111,.12)}
        input::placeholder{color:#b0b5bd}

        .tombol-mata{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:0;cursor:pointer;padding:6px;color:#9ca3af;line-height:1;display:flex;align-items:center;border-radius:7px}
        .tombol-mata:hover{color:#09656F;background:rgba(9,101,111,.06)}

        .pesan-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:10px 13px;border-radius:11px;font-size:12.5px;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;line-height:1.5}
        .pesan-error svg{flex-shrink:0;margin-top:1px}

        .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;background:#09656F;color:#fff;border:0;border-radius:11px;padding:12px 16px;cursor:pointer;font-size:14px;font-weight:700;font-family:inherit;width:100%;transition:background .15s}
        .btn:hover{background:#07535C}
        .btn:disabled{opacity:.6;cursor:not-allowed}

        .bawah-form{margin-top:22px;padding-top:18px;border-top:1px solid #f1f2f4;text-align:center;font-size:12.5px;color:#6b7280}
        .bawah-form a{color:#09656F;font-weight:600}
        .bawah-form a:hover{text-decoration:underline}

        footer{text-align:center;padding:18px 16px 22px;font-size:11px;color:#9ca3af}

        @media(max-width:820px){
            .kotak{grid-template-columns:1fr;max-width:440px}
            .panel-brand{padding:28px 24px}
            .panel-brand .daftar-fitur{display:none}
            .panel-form{padding:28px 24px}
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <span class="dot"></span>
        <strong>SakuSaka</strong>
        <span style="opacity:.75">SMA Negeri 1 Kabanjahe</span>
    </div>
</div>

<div class="kerangka">
    <div class="kotak">

        <div class="panel-brand">
            <div>
                <a href="{{ route('beranda') }}" class="brand">
                    <span class="logo"><x-logo mark tinggi="26" /></span>
                    <div>
                        <div class="nama">SakuSaka</div>
                        <div class="sub">SMA Negeri 1 Kabanjahe</div>
                    </div>
                </a>

                <h2>Selamat datang<br><span>kembali</span></h2>
                <p class="ket">
                    Masuk untuk mengakses presensi, pemilihan, dan layanan sekolah lainnya.
                </p>

                <div class="daftar-fitur">
                    <div class="baris-fitur">
                        <span class="tanda"><x-ikon nama="check-lg" ukuran="10" /></span>
                        <span>Presensi QR sekali pakai</span>
                    </div>
                    <div class="baris-fitur">
                        <span class="tanda"><x-ikon nama="check-lg" ukuran="10" /></span>
                        <span>Pemilihan elektronik dengan suara rahasia</span>
                    </div>
                    <div class="baris-fitur">
                        <span class="tanda"><x-ikon nama="check-lg" ukuran="10" /></span>
                        <span>Pemantauan kehadiran oleh orang tua</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-form">
            <h1>Masuk</h1>
            <p class="subjudul">Gunakan akun yang diberikan sekolah.</p>

            @if ($errors->any())
                <div class="pesan-error">
                    <x-ikon nama="exclamation-triangle" ukuran="15" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.proses') }}" id="form-masuk" autocomplete="off">
                @csrf

                <div class="grup">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <span class="ikon"><x-ikon nama="person" ukuran="15" /></span>
                        <input id="username" name="username" value="{{ old('username') }}"
                               placeholder="Masukkan username" autocomplete="off"
                               autocapitalize="none" spellcheck="false"
                               autofocus required>
                    </div>
                </div>

                <div class="grup">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <span class="ikon"><x-ikon nama="lock" ukuran="15" /></span>
                        <input id="password" type="password" name="password"
                               placeholder="Masukkan password" autocomplete="new-password"
                               required>
                        <button class="tombol-mata" type="button" id="lihat"
                                aria-label="Tampilkan password"><x-ikon nama="eye" ukuran="15" /></button>
                    </div>
                </div>

                <button class="btn" type="submit" id="tombol-masuk">
                    Masuk
                </button>
            </form>

            <div class="bawah-form">
                <a href="{{ route('beranda') }}">← Kembali ke beranda</a>
            </div>
        </div>

    </div>
</div>

<footer>
    &copy; {{ date('Y') }} SMA Negeri 1 Kabanjahe
</footer>

<script>

    const tombolLihat = document.getElementById('lihat');
    const inputPassword = document.getElementById('password');

    const ikonBuka = `<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16" style="display:block"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>`;
    const ikonTutup = `<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16" style="display:block"><path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/><path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/></svg>`;

    tombolLihat.addEventListener('click', function () {
        const tampil = inputPassword.type === 'password';
        inputPassword.type = tampil ? 'text' : 'password';
        tombolLihat.innerHTML = tampil ? ikonTutup : ikonBuka;
        tombolLihat.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
    });

    document.getElementById('form-masuk').addEventListener('submit', function () {
        const t = document.getElementById('tombol-masuk');
        t.disabled = true;
        t.textContent = 'Memproses…';
    });
</script>

</body>
</html>
