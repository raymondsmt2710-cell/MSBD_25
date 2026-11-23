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
