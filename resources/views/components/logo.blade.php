{{--
    Logo SakuSaka.

    Aset dibuat dari asset/logo.jpg dengan latar krem dijadikan transparan
    (lihat public/images/logo-sakusaka.png dan logo-sakusaka-mark.png),
    sehingga aman dipakai di header putih maupun sidebar gelap.

    Pakai:
      <x-logo />                          ikon + teks "SakuSaka" (lebar otomatis)
      <x-logo mark />                     ikon saja
      <x-logo mark tinggi="34" />         ikon saja, tinggi 34px
      <x-logo tinggi="40" kelas="mb-2" /> ikon + teks, tinggi 40px

    Atribut:
      mark    : tampilkan ikon saja (tanpa teks)
      tinggi  : tinggi gambar dalam px (default 36)
      kelas   : kelas CSS tambahan
--}}
@props([
    'mark' => false,
    'tinggi' => 36,
    'kelas' => '',
])

@php
    $berkas = $mark ? 'images/logo-sakusaka-mark.png' : 'images/logo-sakusaka.png';
    // Rasio asli: penuh 584x464, mark 584x332.
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
