@php

    $menuAdmin = [
        'Ringkasan' => [
            ['admin.dashboard', 'Dasbor', 'admin.dashboard', 'grid-3x3'],
        ],
        'Akun' => [
            ['admin.akun.index', 'Akun Pengguna', 'admin.akun.*', 'person-badge'],
        ],
        'Data Master' => [
            ['admin.siswa.index', 'Siswa', 'admin.siswa.*', 'mortarboard'],
            ['admin.guru.index', 'Guru', 'admin.guru.*', 'person-badge'],
            ['admin.orang-tua.index', 'Orang Tua / Wali', 'admin.orang-tua.*', 'person-hearts'],
            ['admin.tahun-ajaran.index', 'Tahun Ajaran', 'admin.tahun-ajaran.*', 'calendar-check'],
            ['admin.mapel.index', 'Mata Pelajaran', 'admin.mapel.*', 'book'],
            ['admin.jabatan.index', 'Master Jabatan', 'admin.jabatan.*', 'award'],
        ],
        'Akademik' => [
            ['admin.kelas.index', 'Kelas & Wali Kelas', 'admin.kelas.*', 'building'],
            ['admin.jadwal.index', 'Jadwal Pelajaran', 'admin.jadwal.*', 'calendar-week'],
        ],
        'Organisasi' => [
            ['admin.organisasi.index', 'Organisasi Sekolah', 'admin.organisasi.*', 'diagram-3'],
            ['admin.pengurus.index', 'Pengurus Kelas & Organisasi', 'admin.pengurus.*', 'people'],
        ],
        'Pemilihan' => [
            ['kelola.pemilihan.index', 'Kelola Pemilihan', 'kelola.pemilihan.*', 'check2-square'],
        ],
        'Pemantauan' => [
            ['admin.presensi.index', 'Presensi Semua Kelas', 'admin.presensi.*', 'clipboard-check'],
            ['admin.kas.index', 'Kas Semua Kelas', 'admin.kas.*', 'cash-coin'],
            ['admin.denah.index', 'Denah Semua Kelas', 'admin.denah.*', 'map'],
        ],
    ];
@endphp
<aside>
    <div class="sidebar-kepala">
        <a href="{{ route('admin.dashboard') }}" class="brand">
            <span class="logo-sidebar">
                <x-logo mark tinggi="24" />
            </span>
            <div>
                <div class="nama">SakuSaka</div>
                <div class="sub">Panel Administrator</div>
            </div>
        </a>

        <button type="button" class="sidebar-toggle"
                aria-expanded="false" aria-controls="menu-admin"
                aria-label="Buka menu navigasi">
            <x-ikon nama="list" ukuran="20" />
        </button>
    </div>

    <nav id="menu-admin">
        @foreach ($menuAdmin as $grup => $item)
            <div class="grup">{{ $grup }}</div>
            @foreach ($item as [$rute, $teks, $pola, $ikon])
                <a class="menu {{ request()->routeIs($pola) ? 'aktif' : '' }}" href="{{ route($rute) }}">
                    <x-ikon :nama="$ikon" ukuran="15" />
                    <span>{{ $teks }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>
</aside>
