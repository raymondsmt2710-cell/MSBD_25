{{--
    Navigasi utama per peran.

    Dipakai SEMUA layout (dashboard, presensi, pemilihan, admin) supaya pengguna
    selalu punya jalan pulang, di halaman mana pun ia berada. Sebelumnya layout
    presensi hanya menampilkan satu tautan sehingga siswa tidak bisa kembali ke
    beranda setelah masuk ke presensi.

    Parameter:
      $gaya : 'nav'    -> tautan horizontal, dibungkus <nav class="nav-links">
              'drawer' -> item vertikal dengan ikon, di dalam .drawer-menu

    Catatan: mode 'nav' membungkus dirinya dengan <nav class="nav-links"> karena
    layouts/dashboard.blade.php memakai @yield('nav') tanpa pembungkus. Layout
    yang SUDAH punya pembungkus sendiri (presensi, pemilihan) harus memanggil
    partial ini langsung di dalam <nav class="nav-links"> yang sama - jangan
    menambah pembungkus kedua, karena itu menghasilkan <nav> bersarang.
--}}
@php
    $gaya = $gaya ?? 'nav';

    $peran = auth()->user()?->role;
    $peran = $peran instanceof \App\Enums\Role ? $peran->value : (string) $peran;

    $menu = match ($peran) {
        'siswa' => [
            ['label' => 'Beranda',   'route' => 'siswa.beranda',             'aktif' => 'siswa.beranda',            'ikon' => 'person-circle'],
            ['label' => 'Presensi',  'route' => 'siswa.presensi.index',      'aktif' => 'siswa.presensi.*',         'ikon' => 'calendar-week'],
            ['label' => 'Perangkat', 'route' => 'siswa.perangkat',           'aktif' => 'siswa.perangkat',          'ikon' => 'people'],
            ['label' => 'Pemilihan', 'route' => 'siswa.pemilihan.index',     'aktif' => 'siswa.pemilihan.*',        'ikon' => 'check2-square'],
        ],
        'guru' => [
            ['label' => 'Beranda',   'route' => 'guru.beranda',              'aktif' => 'guru.beranda',             'ikon' => 'person-circle'],
            ['label' => 'Presensi',  'route' => 'guru.presensi.index',       'aktif' => 'guru.presensi.*',          'ikon' => 'calendar-week'],
            ['label' => 'Perangkat', 'route' => 'perangkat.index',           'aktif' => 'perangkat.*',              'ikon' => 'people'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index',                 'aktif' => 'kas.*',                    'ikon' => 'cash-coin'],
            ['label' => 'Denah',     'route' => 'denah.index',               'aktif' => 'denah.*',                  'ikon' => 'grid-3x3'],
        ],
        // Catatan: guru TIDAK ditawari "Pemilihan" di sini. Guru hanya boleh
        // mengelola pemilihan bila ditunjuk sebagai panitia, dan menu itu
        // ditampilkan terpisah oleh halaman pemilihan itu sendiri.
        'orang_tua' => [
            ['label' => 'Portal Anak', 'route' => 'ortu.index', 'aktif' => 'ortu.*', 'ikon' => 'person-circle'],
        ],
        // Admin: sengaja ringkas. Halaman /perangkat, /kelola/pemilihan, /kas dan
        // /denah sudah tersedia lewat sidebar admin, jadi tidak diulang di sini
        // (dulu tombolnya dobel dan membingungkan).
        'admin' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'aktif' => 'admin.dashboard', 'ikon' => 'grid-3x3'],
        ],
        default => [],
    };
@endphp

@if ($gaya === 'drawer')
    <div class="drawer-menu">
        @foreach ($menu as $m)
            <a class="drawer-item {{ request()->routeIs($m['aktif']) ? 'aktif' : '' }}"
               href="{{ route($m['route']) }}">
                <x-ikon :nama="$m['ikon']" ukuran="17" />
                <span>{{ $m['label'] }}</span>
            </a>
        @endforeach
    </div>
@else
    {{-- Pembungkus <nav class="nav-links"> disediakan di sini, BUKAN di layout
         pemanggil. layouts/dashboard.blade.php memakai @yield('nav') tanpa
         pembungkus, jadi layout guru & siswa bergantung pada partial ini.
         Layout yang sudah punya pembungkus sendiri (presensi, pemilihan) tidak
         boleh menambahkannya lagi - dulu itu menghasilkan <nav> bersarang. --}}
    <nav class="nav-links">
        @foreach ($menu as $m)
            <a href="{{ route($m['route']) }}"
               class="{{ request()->routeIs($m['aktif']) ? 'aktif' : '' }}">{{ $m['label'] }}</a>
        @endforeach
    </nav>
@endif
