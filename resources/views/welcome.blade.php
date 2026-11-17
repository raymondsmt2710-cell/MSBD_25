@php
    $pengguna = auth()->user();
    $peran = $pengguna?->role;

    $pintasan = match ($peran?->value) {
        'admin' => [
            ['label' => 'Kelola Kas Kelas', 'route' => 'kas.index', 'ket' => 'Catat pemasukan, pengeluaran, dan iuran siswa.'],
            ['label' => 'Atur Denah', 'route' => 'denah.index', 'ket' => 'Susun tempat duduk dan peta ruangan sekolah.'],
        ],
        'guru' => [
            ['label' => 'Jadwal Presensi', 'route' => 'guru.presensi.index', 'ket' => 'Buka sesi dan tampilkan QR presensi kelas.'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index', 'ket' => 'Pantau keuangan kelas yang Anda ampu.'],
            ['label' => 'Denah Kelas', 'route' => 'denah.index', 'ket' => 'Lihat posisi duduk siswa dan ruangan sekolah.'],
        ],
        'siswa' => [
            ['label' => 'Riwayat Presensi', 'route' => 'siswa.presensi.riwayat', 'ket' => 'Lihat persentase dan catatan kehadiran Anda.'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index', 'ket' => 'Cek status iuran dan saldo kas kelas.'],
            ['label' => 'Denah Kelas', 'route' => 'denah.index', 'ket' => 'Temukan kursi Anda di denah kelas.'],
        ],
        'orang_tua' => [
            ['label' => 'Kas Kelas', 'route' => 'kas.index', 'ket' => 'Pantau iuran dan keuangan kelas anak Anda.'],
            ['label' => 'Denah Kelas', 'route' => 'denah.index', 'ket' => 'Lihat posisi duduk dan ruangan sekolah.'],
        ],
        default => [],
    };

    $fitur = [
        [
            'judul' => 'Presensi QR Sekali Pakai',
            'ket' => 'Guru membuka sesi, siswa memindai QR yang berganti otomatis. Rekap hadir, izin, sakit, dan alpa langsung tercatat.',
            'ikon' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM15 14h2v2h-2zM19 14h1v2h-1zM14 19h3v1h-3zM19 18h1v3h-1z',
        ],
        [
            'judul' => 'Kas Kelas Transparan',
            'ket' => 'Catat pemasukan dan pengeluaran, kelola iuran bulanan, verifikasi pembayaran, lalu ekspor laporan CSV.',
            'ikon' => 'M3 6h18v12H3zM3 10h18M7 15h3',
        ],
        [
            'judul' => 'Denah Interaktif',
            'ket' => 'Susun posisi duduk, acak kursi, dan telusuri peta ruangan sekolah beserta jadwal yang sedang berlangsung.',
            'ikon' => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
        ],
        [
            'judul' => 'Roster Digital',
            'ket' => 'Daftar siswa, wali kelas, jadwal pelajaran, dan pengurus kelas tersimpan rapi dalam satu basis data terpusat.',
            'ikon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM22 21v-2a4 4 0 00-3-3.87M16 3.13A4 4 0 0119 7a4 4 0 01-3 3.87',
        ],
    ];
@endphp

<x-app-layout judul="Beranda">
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-sekolah-ink via-sekolah-teal to-sekolah-blue px-6 py-12 text-white shadow-lembut sm:px-10 sm:py-14">
        <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-sekolah-amber/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>

        <div class="relative max-w-2xl">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/80 ring-1 ring-white/20">
                SMA Negeri 1 Kabanjahe
            </span>

            <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">
                E-Learning Terpadu untuk<br class="hidden sm:block"> Kegiatan Kelas Sehari-hari
            </h1>

            <p class="mt-4 max-w-xl text-sm leading-relaxed text-white/80 sm:text-base">
                Presensi, kas kelas, dan denah kelas dalam satu sistem berbasis data. Cepat, transparan, dan mudah diakses guru, siswa, serta orang tua.
            </p>

            <div class="mt-7 flex flex-wrap items-center gap-3">
                @auth
                    <a href="{{ route($peran->home()) }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-sekolah-teal shadow-sm transition hover:bg-white/90">
                        Buka Beranda {{ $peran->label() }}
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6l6 6-6 6"/>
                        </svg>
                    </a>
                    <span class="text-sm text-white/70">Masuk sebagai <strong class="font-semibold text-white">{{ $pengguna->username }}</strong> · {{ $peran->label() }}</span>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-sekolah-teal shadow-sm transition hover:bg-white/90">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14"/>
                        </svg>
                        Masuk ke Akun
                    </a>
                    <span class="text-sm text-white/70">Gunakan akun sekolah yang diberikan admin.</span>
                @endauth
            </div>
        </div>
    </section>

    @auth
        @if ($pintasan)
            <section class="mt-8">
                <p class="label-kapital">Pintasan</p>
                <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($pintasan as $item)
                        <a href="{{ route($item['route']) }}" class="group kartu flex flex-col p-5 transition hover:-translate-y-0.5 hover:shadow-lembut">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sekolah-teal/10 text-sekolah-teal">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12h14m-6-6l6 6-6 6"/>
                                </svg>
                            </span>
                            <strong class="mt-3 text-sm font-bold text-sekolah-ink group-hover:text-sekolah-teal">{{ $item['label'] }}</strong>
                            <span class="mt-1 text-xs leading-relaxed text-gray-500">{{ $item['ket'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endauth

    <section class="mt-9">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="label-kapital">Fitur</p>
                <h2 class="mt-1 text-xl font-extrabold tracking-tight text-sekolah-ink">Satu sistem untuk banyak kebutuhan kelas</h2>
            </div>
            <span class="text-xs text-gray-500">Manajemen Sistem Basis Data · USU</span>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach ($fitur as $item)
                <article class="kartu p-5">
                    <div class="flex items-start gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sekolah-amber/10 text-sekolah-amber">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['ikon'] }}"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-sekolah-ink">{{ $item['judul'] }}</h3>
                            <p class="mt-1 text-xs leading-relaxed text-gray-500">{{ $item['ket'] }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</x-app-layout>
