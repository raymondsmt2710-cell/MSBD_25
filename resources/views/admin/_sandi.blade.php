{{-- Password sementara hanya ditampilkan sekali (flash), tidak pernah disimpan dalam bentuk asli. --}}
@if (session('sandi_baru'))
    <div class="ok">
        Password sementara: <span class="sandi">{{ session('sandi_baru') }}</span>
        — catat dan serahkan kepada pemilik akun. Password ini tidak akan ditampilkan lagi.
    </div>
@endif
