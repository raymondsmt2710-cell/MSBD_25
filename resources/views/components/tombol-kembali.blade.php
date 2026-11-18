{{--
    Tombol "kembali ke halaman sebelumnya".
    Dipasang sekali di tiap layout, jadi halaman baru otomatis mendapatkannya.
    Tujuan dihitung TujuanKembali; null berarti tombolnya tidak dirender sama
    sekali (beranda, tamu, atau referer yang tidak bisa dipakai).
--}}
@props(['tujuan' => null])

@php
    $tujuan ??= \App\Support\TujuanKembali::untuk(request(), auth()->user());
@endphp

@if ($tujuan !== null)
    <div class="kembali-wrap">
        <a class="tombol-kembali" href="{{ $tujuan }}">
            <x-ikon nama="arrow-left" ukuran="15" /> Kembali
        </a>
    </div>
@endif
