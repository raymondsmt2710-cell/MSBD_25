{{--
    Tombol "kembali ke dashboard".
    Dipasang sekali di tiap layout, jadi halaman baru otomatis mendapatkannya.
    Tujuan dihitung TujuanKembali dan selalu dashboard peran pengguna; null
    berarti tombolnya tidak dirender sama sekali (tamu atau dashboard itu
    sendiri).
--}}
@props(['tujuan' => null])

@php
    $tujuan ??= \App\Support\TujuanKembali::untuk(request(), auth()->user());
@endphp

@if ($tujuan !== null)
    <div class="kembali-wrap">
        <a class="tombol-kembali" href="{{ $tujuan }}">
            <x-ikon nama="arrow-left" ukuran="15" /> Kembali ke Dashboard
        </a>
    </div>
@endif
