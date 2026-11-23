<div class="drawer-menu">
    <a class="drawer-item" href="{{ route('siswa.presensi.index') }}">
        <span class="ikon"><x-ikon nama="qr-code-scan" ukuran="15" /></span>
        <span class="judul">Presensi</span>
    </a>

    <a class="drawer-item" href="{{ route('siswa.presensi.riwayat') }}#roster">
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
