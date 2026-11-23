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
