{{--
    Isi panel hamburger untuk siswa.
    Satu baris per tujuan: ikon + judul, tanpa keterangan, supaya kartunya
    pendek dan panel tidak perlu digulir di layar kecil.
--}}
<div class="drawer-menu">
    <a class="drawer-item" href="{{ route('dashboard.presensi.riwayat') }}#ringkasan">
        <span class="ikon"><x-ikon nama="qr-code-scan" ukuran="15" /></span>
        <span class="judul">Presensi</span>
    </a>

    <a class="drawer-item" href="{{ route('dashboard.presensi.riwayat') }}#roster">
        <span class="ikon"><x-ikon nama="calendar-week" ukuran="15" /></span>
        <span class="judul">Roster</span>
    </a>

    <a class="drawer-item" href="{{ route('siswa.perangkat') }}">
        <span class="ikon"><x-ikon nama="diagram-3" ukuran="15" /></span>
        <span class="judul">Perangkat Kelas</span>
    </a>

    <a class="drawer-item" href="{{ route('siswa.pemilihan.index') }}">
        <span class="ikon"><x-ikon nama="check2-square" ukuran="15" /></span>
        <span class="judul">Pemilihan</span>
    </a>
</div>
