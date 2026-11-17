@props(['judul' => null])

@php
    $pengguna = auth()->user();
    $peran = $pengguna?->role;

    $navigasi = match ($peran?->value) {
        'admin' => [
            ['label' => 'Beranda', 'route' => 'admin.dashboard'],
            ['label' => 'Pemilihan', 'route' => 'kelola.pemilihan.index'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index'],
            ['label' => 'Denah', 'route' => 'denah.index'],
        ],
        'guru' => [
            ['label' => 'Beranda', 'route' => 'guru.dashboard'],
            ['label' => 'Jadwal Presensi', 'route' => 'guru.presensi.index'],
            ['label' => 'Pemilihan', 'route' => 'kelola.pemilihan.index'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index'],
            ['label' => 'Denah', 'route' => 'denah.index'],
        ],
        'siswa' => [
            ['label' => 'Beranda', 'route' => 'siswa.dashboard'],
            ['label' => 'Riwayat Presensi', 'route' => 'siswa.presensi.riwayat'],
            ['label' => 'Pemilihan', 'route' => 'siswa.pemilihan.index'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index'],
            ['label' => 'Denah', 'route' => 'denah.index'],
        ],
        'orang_tua' => [
            ['label' => 'Beranda', 'route' => 'ortu.dashboard'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index'],
            ['label' => 'Denah', 'route' => 'denah.index'],
        ],
        default => [],
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $judul ? $judul.' — ' : '' }}E-Learning SMAN 1 Kabanjahe</title>

    <link rel="icon" href="{{ asset('images/logo-sekolah.png') }}" type="image/png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-gray-800 antialiased" x-data="{ menuTerbuka: false }">
    <header class="sticky top-0 z-40 border-b border-gray-100 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ $peran ? route($peran->home()) : route('beranda') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo SMAN 1 Kabanjahe"
                     class="h-11 w-11 rounded-xl object-contain ring-1 ring-gray-100">
                <span class="leading-tight">
                    <span class="block text-sm font-extrabold tracking-tight text-sekolah-ink">E-Learning</span>
                    <span class="block text-[11px] font-medium text-gray-500">SMA Negeri 1 Kabanjahe</span>
                </span>
            </a>

            @if ($peran)
                <nav class="hidden items-center gap-1 lg:flex">
                    @foreach ($navigasi as $item)
                        <a href="{{ route($item['route']) }}"
                           class="tautan-nav {{ request()->routeIs($item['route']) ? 'tautan-nav-aktif' : '' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            @endif

            <div class="flex items-center gap-2">
                @if ($pengguna)
                    <div class="hidden text-right sm:block">
                        <span class="block text-sm font-semibold text-gray-800">{{ $pengguna->username }}</span>
                        <span class="block text-[11px] font-medium uppercase tracking-wider text-sekolah-teal">
                            {{ $peran?->label() }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="tombol-garis px-3 py-2">Keluar</button>
                    </form>
                @endif

                <button type="button" @click="menuTerbuka = ! menuTerbuka"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-600 lg:hidden"
                        aria-label="Buka menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        @if ($peran)
            <div x-show="menuTerbuka" x-cloak x-transition class="border-t border-gray-100 bg-white px-4 py-3 lg:hidden">
                <div class="flex flex-col gap-1">
                    @foreach ($navigasi as $item)
                        <a href="{{ route($item['route']) }}"
                           class="tautan-nav {{ request()->routeIs($item['route']) ? 'tautan-nav-aktif' : '' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
                    <span class="text-sm text-gray-500">{{ $pengguna->username }} · {{ $peran->label() }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="tombol-garis px-3 py-2">Keluar</button>
                    </form>
                </div>
            </div>
        @endif
    </header>

    @isset($header)
        <div class="border-b border-gray-100 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </div>
    @endisset

    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.2 14.2A1 1 0 003 19.5h18a1 1 0 00.87-1.5l-8.2-14.2a1 1 0 00-1.74 0z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="border-t border-gray-100 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-5 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <span>© {{ date('Y') }} E-Learning SMA Negeri 1 Kabanjahe</span>
            <span>Manajemen Sistem Basis Data · Universitas Sumatera Utara</span>
        </div>
    </footer>
</body>
</html>
