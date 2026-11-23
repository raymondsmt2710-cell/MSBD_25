@props([
    'mark' => false,
    'tinggi' => 36,
    'kelas' => '',
])

@php
    $berkas = $mark ? 'images/logo-sakusaka-mark.png' : 'images/logo-sakusaka.png';

    $rasio = $mark ? (584 / 332) : (584 / 464);
    $lebar = (int) round(((int) $tinggi) * $rasio);
@endphp

<img
    src="{{ asset($berkas) }}"
    alt="SakuSaka"
    width="{{ $lebar }}"
    height="{{ (int) $tinggi }}"
    class="logo-sakusaka {{ $kelas }}"
    style="height:{{ (int) $tinggi }}px;width:auto;display:block"
    loading="eager"
    decoding="async"
>
