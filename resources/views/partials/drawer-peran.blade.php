{{--
    Isi panel hamburger sesuai peran.

    Layout presensi & pemilihan dipakai beberapa peran sekaligus, jadi drawer-nya
    tidak bisa dipatok ke satu partial. Partial ini memilih menu yang tepat:
      - siswa      -> menu-cepat-siswa (kartu menuju anchor halaman)
      - guru       -> menu-cepat-guru
      - lainnya    -> daftar tautan dari nav-utama (gaya drawer)
--}}
@php
    $peran = auth()->user()?->role;
    $peran = $peran instanceof \App\Enums\Role ? $peran->value : (string) $peran;
@endphp

@if ($peran === 'siswa')
    @include('partials.menu-cepat-siswa')
@elseif ($peran === 'guru')
    @include('partials.menu-cepat-guru')
@else
    @include('partials.nav-utama', ['gaya' => 'drawer'])
@endif
