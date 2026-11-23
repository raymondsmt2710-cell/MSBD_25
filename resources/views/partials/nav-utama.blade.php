@php
    $gaya = $gaya ?? 'nav';

    $peran = auth()->user()?->role;
    $peran = $peran instanceof \App\Enums\Role ? $peran->value : (string) $peran;

    $menu = match ($peran) {
        'siswa', 'bendahara_kas' => [
            ['label' => 'Beranda',   'route' => 'siswa.beranda',             'aktif' => 'siswa.beranda',            'ikon' => 'person-circle'],
            ['label' => 'Presensi',  'route' => 'siswa.presensi.index',      'aktif' => 'siswa.presensi.*',         'ikon' => 'calendar-week'],
            ['label' => 'Perangkat', 'route' => 'siswa.perangkat',           'aktif' => 'siswa.perangkat',          'ikon' => 'people'],
            ['label' => 'Pemilihan', 'route' => 'siswa.pemilihan.index',     'aktif' => 'siswa.pemilihan.*',        'ikon' => 'check2-square'],


            ['label' => 'Keuangan Kelas', 'route' => 'kas.index',            'aktif' => 'kas.*',                    'ikon' => 'cash-coin'],
            ['label' => 'Denah',     'route' => 'denah.index',               'aktif' => 'denah.*',                  'ikon' => 'grid-3x3'],
        ],
        'guru' => [
            ['label' => 'Beranda',   'route' => 'guru.beranda',              'aktif' => 'guru.beranda',             'ikon' => 'person-circle'],
            ['label' => 'Presensi',  'route' => 'guru.presensi.index',       'aktif' => 'guru.presensi.*',          'ikon' => 'calendar-week'],
            ['label' => 'Perangkat', 'route' => 'perangkat.index',           'aktif' => 'perangkat.*',              'ikon' => 'people'],
            ['label' => 'Keuangan Kelas', 'route' => 'kas.index',            'aktif' => 'kas.*',                    'ikon' => 'cash-coin'],
            ['label' => 'Denah',     'route' => 'denah.index',               'aktif' => 'denah.*',                  'ikon' => 'grid-3x3'],
        ],



        'orang_tua' => [
            ['label' => 'Portal Anak', 'route' => 'ortu.index', 'aktif' => 'ortu.*', 'ikon' => 'person-circle'],


            ['label' => 'Keuangan Anak', 'route' => 'kas.index', 'aktif' => 'kas.*', 'ikon' => 'cash-coin'],
        ],



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
    <nav class="nav-links">
        @foreach ($menu as $m)
            <a href="{{ route($m['route']) }}"
               class="{{ request()->routeIs($m['aktif']) ? 'aktif' : '' }}">{{ $m['label'] }}</a>
        @endforeach
    </nav>
@endif
