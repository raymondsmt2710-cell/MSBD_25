@extends('layouts.pemilihan')
@section('judul', 'Hasil Pemilihan')
@section('isi')
@php $id = $pemilihan->id_pemilihan; @endphp

<h1>Perolehan Suara</h1>
<p class="subjudul">{{ $pemilihan->judul }} — pemilihan telah ditutup.</p>

<div class="kartu">
    <div class="grid grid-2">
        <div class="stat">
            <span class="angka">{{ $partisipasi->sudah_memilih ?? 0 }} / {{ $partisipasi->total_pemilih ?? 0 }}</span>
            <span class="label">Pemilih menggunakan hak suara</span>
        </div>
        <div class="stat">
            <span class="angka">{{ number_format((float) ($partisipasi->persentase ?? 0), 2) }}%</span>
            <span class="label">Partisipasi</span>
        </div>
    </div>
</div>

<div class="kartu">
    <h2>Perolehan Suara</h2>

    @php $total = max(1, (int) $hasil->sum('jumlah_suara')); @endphp

    @forelse ($hasil as $h)
        @php
            $persen = ((int) $h->jumlah_suara) * 100 / $total;
            $menang = $tertinggi !== null && (int) $h->jumlah_suara === (int) $tertinggi && (int) $tertinggi > 0;
        @endphp
        <div style="margin-bottom:18px">
            <div class="meta" style="margin-bottom:6px">
                <span>
                    <span class="nomor">No. {{ str_pad((string) $h->nomor_urut, 2, '0', STR_PAD_LEFT) }}</span>
                    <strong>{{ $h->nama_ketua }}</strong>@if ($h->nama_wakil) &amp; {{ $h->nama_wakil }}@endif
                    @if ($menang)<span class="badge berlangsung">Terbanyak</span>@endif
                </span>
                <span><strong>{{ (int) $h->jumlah_suara }}</strong> suara ({{ number_format($persen, 1) }}%)</span>
            </div>
            <div class="bar {{ $menang ? 'menang' : '' }}"><span style="width:{{ $persen }}%"></span></div>
        </div>
    @empty
        <div class="kosong">Belum ada kandidat pada pemilihan ini.</div>
    @endforelse

    <p class="kecil" style="margin:16px 0 0">
        Total suara sah: <strong>{{ (int) $hasil->sum('jumlah_suara') }}</strong>.
        Hasil dihitung langsung dari database menggunakan view <code>v_hasil_pemilihan</code>.
    </p>
</div>

<div class="kartu">
    <a class="btn garis" href="{{ route('siswa.pemilihan.index') }}">Kembali ke Daftar Pemilihan</a>
</div>
@endsection
