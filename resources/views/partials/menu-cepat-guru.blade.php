{{--
    Isi panel hamburger untuk guru.

    Isinya harus SAMA dengan nav desktop (partials.nav-utama, cabang 'guru'):
    Presensi, Perangkat, Kas Kelas, Denah. Sebelumnya hanya "Presensi" yang
    masuk, sehingga tiga tujuan lain hilang begitu layar menyempit ke ponsel.

    "Beranda" tidak diulang di sini karena logo di tengah bar sudah menuju
    beranda, dan panel cukup pendek supaya tidak perlu digulir.

    Guru tidak punya akses voting, jadi "Pemilihan" tidak pernah muncul.
    Satu baris per tujuan: ikon + judul, tanpa keterangan.
--}}
<div class="drawer-menu">
    <a class="drawer-item" href="{{ route('guru.presensi.index') }}">
        <span class="ikon"><x-ikon nama="calendar-week" ukuran="15" /></span>
        <span class="judul">Presensi</span>
    </a>

    <a class="drawer-item" href="{{ route('perangkat.index') }}">
        <span class="ikon"><x-ikon nama="diagram-3" ukuran="15" /></span>
        <span class="judul">Perangkat</span>
    </a>

    <a class="drawer-item" href="{{ route('kas.index') }}">
        <span class="ikon"><x-ikon nama="cash-coin" ukuran="15" /></span>
        <span class="judul">Kas Kelas</span>
    </a>

    <a class="drawer-item" href="{{ route('denah.index') }}">
        <span class="ikon"><x-ikon nama="grid-3x3" ukuran="15" /></span>
        <span class="judul">Denah</span>
    </a>
</div>
