<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Admin') - SakuSaka</title>
    <link rel="icon" href="{{ asset('images/logo-sakusaka-mark.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:Figtree,system-ui,-apple-system,sans-serif;background:#f6f7f8;color:#1f2937}
        a{color:inherit;text-decoration:none}

        .topbar{background:#09656F;color:#fff;padding:9px 22px;font-size:12px}
        .topbar-inner{max-width:1180px;margin:0 auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .topbar .dot{width:6px;height:6px;border-radius:999px;background:#C97A14;display:inline-block;animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}
        .kerangka{display:grid;grid-template-columns:240px 1fr;min-height:100vh}
        aside{background:#043d44;color:#d7eef0;padding:18px 12px;position:sticky;top:0;height:100vh;overflow-y:auto}
        .brand{display:flex;align-items:center;gap:10px;padding:0 8px 16px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:10px}
        .brand .logo{width:34px;height:34px;border-radius:10px;background:#C97A14;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px}
        .brand .nama{font-size:13px;font-weight:800;color:#fff;line-height:1.1}
        .brand .sub{font-size:11px;color:#8fc3c8}

        .logo-sidebar{display:inline-flex;align-items:center;justify-content:center;background:#F9F5E9;border-radius:9px;padding:4px 6px;flex-shrink:0}
        .grup{font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:#7fb1b6;font-weight:700;padding:14px 10px 6px}
        aside a.menu{display:block;padding:7px 10px;border-radius:9px;font-size:13px;color:#d7eef0}
        aside a.menu:hover{background:rgba(255,255,255,.07);color:#fff}
        aside a.menu.aktif{background:#09656F;color:#fff;font-weight:600}
        .atas{background:#fff;border-bottom:1px solid #eef0f2;padding:10px 22px;display:flex;justify-content:space-between;align-items:center;gap:10px;position:sticky;top:0;z-index:20}
        .atas .jalur{font-size:12px;color:#9ca3af}

        .nav-profil{display:flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:999px;background:rgba(9,101,111,.08);color:#09656F;flex-shrink:0}
        .nav-profil:hover{background:rgba(9,101,111,.15)}
        .kembali-wrap{margin-bottom:14px}
        .tombol-kembali{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;background:rgba(9,101,111,.07);color:#09656F;font-size:12px;font-weight:600;transition:background .15s}
        .tombol-kembali:hover{background:rgba(9,101,111,.14)}
        main{padding:22px;max-width:1180px}
        h1{font-size:22px;margin:0 0 4px}
        .subjudul{font-size:13px;color:#6b7280;margin:0 0 18px}
        .kepala{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap}
        .kartu{background:#fff;border:1px solid #eef0f2;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(16,24,40,.05);margin-bottom:16px}
        .kartu h2{margin:0 0 12px;font-size:15px}
        .ok{background:#dcfce7;color:#166534;padding:11px 14px;border-radius:12px;margin-bottom:14px;font-size:13px}
        .err{background:#fee2e2;color:#991b1b;padding:11px 14px;border-radius:12px;margin-bottom:14px;font-size:13px}
        .info{background:#e0f2fe;color:#075985;padding:11px 14px;border-radius:12px;margin-bottom:14px;font-size:13px}
        .btn{display:inline-flex;align-items:center;gap:6px;background:#09656F;color:#fff;border:0;border-radius:10px;padding:8px 14px;cursor:pointer;font-size:13px;font-weight:600;font-family:inherit;white-space:nowrap}
        .btn:hover{background:#07535C}
        .btn.kecil{padding:5px 10px;font-size:12px;border-radius:8px}
        .btn.oranye{background:#C97A14}.btn.merah{background:#b91c1c}.btn.abu{background:#6b7280}
        .btn.garis{background:#fff;color:#09656F;border:1px solid rgba(9,101,111,.25)}
        .btn.garis:hover{background:rgba(9,101,111,.05)}
        table{width:100%;border-collapse:collapse}
        th,td{padding:9px 8px;border-bottom:1px solid #f1f2f4;text-align:left;font-size:13px;vertical-align:middle}
        th{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;font-weight:700}
        tr.redup td{opacity:.5}
        input,select,textarea{padding:8px 10px;border:1px solid #dfe2e6;border-radius:9px;font-size:13px;font-family:inherit;width:100%;background:#fff}
        input[type=checkbox],input[type=radio]{width:auto}
        input:focus,select:focus,textarea:focus{outline:none;border-color:#09656F;box-shadow:0 0 0 3px rgba(9,101,111,.12)}
        label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px}
        .bidang{margin-bottom:12px}
        .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0 14px}
        .filter{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:12px}
        .filter > *{width:auto;min-width:140px}
        .badge{display:inline-block;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:#e5e7eb;color:#374151}
        .badge.hijau,.badge.aktif,.badge.hadir,.badge.disetujui,.badge.pemasukan{background:#dcfce7;color:#166534}
        .badge.merah,.badge.nonaktif,.badge.alpa,.badge.ditolak,.badge.keluar,.badge.pengeluaran{background:#fee2e2;color:#991b1b}
        .badge.kuning,.badge.izin,.badge.sakit,.badge.menunggu,.badge.pindah{background:#fef9c3;color:#854d0e}
        .badge.biru,.badge.lulus,.badge.admin{background:#dbeafe;color:#1e40af}
        .grid{display:grid;gap:14px}
        .grid-4{grid-template-columns:repeat(auto-fill,minmax(180px,1fr))}
        .grid-2{grid-template-columns:repeat(auto-fit,minmax(320px,1fr))}

        .grid-dua{grid-template-columns:minmax(0,2fr) minmax(280px,1fr);align-items:start}
        .stat{display:flex;flex-direction:column;gap:2px}
        .stat .angka{font-size:22px;font-weight:800;color:#09656F;line-height:1.1}
        .stat .label{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;font-weight:700}
        .aksi-baris{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
        .aksi-baris form{display:inline}
        .kecil{font-size:11px;color:#9ca3af}
        .kosong{text-align:center;padding:30px 16px;color:#9ca3af;font-size:13px}
        .paginasi{margin-top:14px}
        .paginasi nav{display:flex;gap:4px;flex-wrap:wrap}
        .paginasi a,.paginasi span{padding:6px 10px;border-radius:8px;font-size:12px;border:1px solid #e5e7eb;color:#4b5563;background:#fff}
        .paginasi span[aria-current]{background:#09656F;color:#fff;border-color:#09656F}
        .uang{font-variant-numeric:tabular-nums;text-align:right;white-space:nowrap}
        .sandi{font-family:ui-monospace,monospace;background:#fff;padding:2px 8px;border-radius:6px;border:1px dashed #16a34a}
        @media(max-width:860px){
            .kerangka{grid-template-columns:1fr}
            aside{position:static;height:auto}
            main{padding:16px}

            .kartu{overflow-x:auto;-webkit-overflow-scrolling:touch}

            .grid-dua{grid-template-columns:minmax(0,1fr)}

            .filter{flex-direction:column;align-items:stretch}
            .filter > *{width:100%}
        }
        @media(max-width:640px){

            .atas .badge{display:none}
        }
    </style>
    @stack('gaya')
</head>
@include('partials.css-sidebar-admin')
<body>
@php

    $menu = [
        'Ringkasan' => [
            ['admin.dashboard', 'Dasbor', 'admin.dashboard'],
        ],
        'Akun' => [
            ['admin.akun.index', 'Akun Pengguna', 'admin.akun.*'],
        ],
        'Data Master' => [
            ['admin.siswa.index', 'Siswa', 'admin.siswa.*'],
            ['admin.guru.index', 'Guru', 'admin.guru.*'],
            ['admin.orang-tua.index', 'Orang Tua / Wali', 'admin.orang-tua.*'],
            ['admin.tahun-ajaran.index', 'Tahun Ajaran', 'admin.tahun-ajaran.*'],
            ['admin.mapel.index', 'Mata Pelajaran', 'admin.mapel.*'],
            ['admin.jabatan.index', 'Master Jabatan', 'admin.jabatan.*'],
        ],
        'Akademik' => [
            ['admin.kelas.index', 'Kelas & Wali Kelas', 'admin.kelas.*'],
            ['admin.jadwal.index', 'Jadwal Pelajaran', 'admin.jadwal.*'],
        ],
        'Organisasi' => [
            ['admin.organisasi.index', 'Organisasi Sekolah', 'admin.organisasi.*'],
            ['admin.pengurus.index', 'Pengurus Kelas & Organisasi', 'admin.pengurus.*'],
        ],
        'Pemilihan' => [
            ['kelola.pemilihan.index', 'Kelola Pemilihan', 'kelola.pemilihan.*'],
        ],
        'Pemantauan' => [
            ['admin.presensi.index', 'Presensi Semua Kelas', 'admin.presensi.*'],
            ['admin.kas.index', 'Kas Semua Kelas', 'admin.kas.*'],
            ['admin.denah.index', 'Denah Semua Kelas', 'admin.denah.*'],
        ],
    ];
@endphp
<div class="kerangka kerangka-admin">
    @include('partials.sidebar-admin')

    <div>
        <div class="topbar">
            <div class="topbar-inner">
                <span class="dot"></span>
                <strong>Sistem Informasi Sekolah</strong>
                <span style="opacity:.75">SMA Negeri 1 Kabanjahe</span>
            </div>
        </div>

        <div class="atas">
            <span class="jalur">@yield('jalur', 'Administrator')</span>
            <div class="aksi-baris">
                <a href="{{ route('akun.profil') }}" class="nav-profil" aria-label="Profil dan ganti password">
                    <x-ikon nama="person-circle" ukuran="20" />
                </a>
                <span class="badge admin">{{ auth()->user()->username }} · admin</span>
            </div>
        </div>

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
    </div>
</div>
<script>

    document.addEventListener('submit', function (e) {
        var pesan = e.target.dataset ? e.target.dataset.konfirmasi : null;
        if (pesan && ! window.confirm(pesan)) { e.preventDefault(); }
    });
</script>
@stack('skrip')
@include('partials.script-sidebar-admin')
</body>
</html>
