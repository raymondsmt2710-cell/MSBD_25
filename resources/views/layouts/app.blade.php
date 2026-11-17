<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul') – E-Learning SMAN 1 Kabanjahe</title>
    <style>
        body{font-family:system-ui,sans-serif;margin:0;background:#f4f5f7;color:#1f2937}
        header{background:#1e3a8a;color:#fff;padding:12px 20px;display:flex;justify-content:space-between;align-items:center}
        main{max-width:960px;margin:20px auto;padding:0 16px}
        .card{background:#fff;border-radius:8px;padding:16px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
        table{width:100%;border-collapse:collapse}th,td{padding:8px;border-bottom:1px solid #e5e7eb;text-align:left;font-size:14px}
        .btn{background:#1e3a8a;color:#fff;border:0;border-radius:6px;padding:8px 14px;cursor:pointer;font-size:14px}
        .btn.merah{background:#b91c1c}.btn.abu{background:#6b7280}
        .ok{background:#dcfce7;color:#166534;padding:10px;border-radius:6px;margin-bottom:12px}
        .err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:6px;margin-bottom:12px}
        input,select{padding:7px;border:1px solid #d1d5db;border-radius:6px}
        .badge{padding:2px 8px;border-radius:10px;font-size:12px;background:#e5e7eb}
        .hadir{background:#dcfce7}.izin,.sakit{background:#fef9c3}.alpa{background:#fee2e2}
    </style>
</head>
<body>
<header>
    <strong>E-Learning SMAN 1 Kabanjahe</strong>
    @auth
        <nav style="display:flex;align-items:center;gap:4px;flex-wrap:wrap">
            @if (auth()->user()->role === 'siswa')
                <a href="{{ route('siswa.presensi.riwayat') }}"
                   style="color:#fff;text-decoration:none;padding:6px 10px;border-radius:6px;font-size:13px;{{ request()->routeIs('siswa.presensi.*') ? 'background:rgba(255,255,255,.18)' : '' }}">
                    Presensi
                </a>
                <a href="{{ route('siswa.pemilihan.index') }}"
                   style="color:#fff;text-decoration:none;padding:6px 10px;border-radius:6px;font-size:13px;{{ request()->routeIs('siswa.pemilihan.*') ? 'background:rgba(255,255,255,.18)' : '' }}">
                    Pemilihan
                </a>
            @else
                <a href="{{ route('guru.presensi.index') }}"
                   style="color:#fff;text-decoration:none;padding:6px 10px;border-radius:6px;font-size:13px;{{ request()->routeIs('guru.presensi.*') ? 'background:rgba(255,255,255,.18)' : '' }}">
                    Presensi
                </a>
                <a href="{{ route('kelola.pemilihan.index') }}"
                   style="color:#fff;text-decoration:none;padding:6px 10px;border-radius:6px;font-size:13px;{{ request()->routeIs('kelola.pemilihan.*') ? 'background:rgba(255,255,255,.18)' : '' }}">
                    Pemilihan
                </a>
            @endif
        </nav>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <span>{{ auth()->user()->username }} ({{ auth()->user()->role }})</span>
            <button class="btn abu" type="submit">Keluar</button>
        </form>
    @endauth
</header>
<main>
    @if (session('success'))<div class="ok">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="err">{{ session('error') }}</div>@endif
    @yield('isi')
</main>
</body>
</html>
