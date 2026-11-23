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
