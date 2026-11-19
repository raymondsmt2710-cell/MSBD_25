{{--
    Isi panel hamburger untuk guru.
    Guru tidak punya akses voting, jadi "Pemilihan" tidak pernah muncul di sini.
--}}
<div class="drawer-menu">
    <a class="drawer-item" href="{{ route('guru.presensi.index') }}">
        <span class="ikon"><x-ikon nama="qr-code-scan" ukuran="15" /></span>
        <span class="judul">Presensi</span>
    </a>
</div>
