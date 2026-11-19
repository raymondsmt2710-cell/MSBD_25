{{--
    Sidebar panel administrator.

    Dipakai layout admin DAN halaman lain yang dibuka admin (/perangkat,
    /kelola/pemilihan, /kas, /denah), supaya admin selalu punya navigasi yang
    sama di mana pun ia berada.

    Butuh CSS .kerangka / aside / .brand / .grup / aside a.menu yang sudah ada
    di layout pemanggil (lihat layouts/admin.blade.php).
--}}
@php
    $menuAdmin = [
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
<aside>
    <a href="{{ route('admin.dashboard') }}" class="brand">
        <span class="logo-sidebar">
            <x-logo mark tinggi="24" />
        </span>
        <div>
            <div class="nama">SakuSaka</div>
            <div class="sub">Panel Administrator</div>
        </div>
    </a>
    <nav>
        @foreach ($menuAdmin as $grup => $item)
            <div class="grup">{{ $grup }}</div>
            @foreach ($item as [$rute, $teks, $pola])
                <a class="menu {{ request()->routeIs($pola) ? 'aktif' : '' }}" href="{{ route($rute) }}">{{ $teks }}</a>
            @endforeach
        @endforeach
    </nav>
</aside>
