@extends('layouts.pemilihan')
@section('judul', 'Monitor Pemilihan')
@section('isi')
@php
    $id = $pemilihan->id_pemilihan;
    $aktif = $pemilihan->status === 'berlangsung';
@endphp

<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <h1>{{ $pemilihan->judul }}</h1>
        <p class="subjudul">
            <span class="badge {{ $pemilihan->status }}">{{ $pemilihan->status }}</span>
            &nbsp;{{ $pemilihan->waktu_mulai?->format('d M Y H:i') }} s/d {{ $pemilihan->waktu_selesai?->format('d M Y H:i') }}
        </p>
    </div>
    <div class="aksi-baris">
        <a class="btn garis" href="{{ route('kelola.pemilihan.kandidat', $id) }}">Kandidat</a>
        <a class="btn garis" href="{{ route('kelola.pemilihan.pemilih', $id) }}">Pemilih</a>
        @if (auth()->user()->role === 'admin')
            <a class="btn garis" href="{{ route('kelola.pemilihan.panitia', $id) }}">Panitia</a>
        @endif
        <a class="btn garis" href="{{ route('kelola.pemilihan.log', $id) }}">Log</a>
    </div>
</div>

@if ($waktuLewat)
    <div class="err">
        Peringatan: waktu sudah lewat, tetapi status masih <strong>berlangsung</strong>.
        Pemungutan suara sudah berhenti sendiri; tekan <strong>Tutup Pemilihan</strong> agar hasil dapat dilihat siswa.
    </div>
@endif

<div class="kartu">
    <div class="grid grid-2">
        <div class="stat">
            <span class="angka">{{ $partisipasi->sudah_memilih ?? 0 }} / {{ $partisipasi->total_pemilih ?? 0 }}</span>
            <span class="label">Sudah memilih</span>
        </div>
        <div class="stat">
            <span class="angka">{{ number_format((float) ($partisipasi->persentase ?? 0), 2) }}%</span>
            <span class="label">Partisipasi</span>
        </div>
    </div>
</div>

<div class="kartu">
    <h2>Perolehan Suara Sementara</h2>
    <p class="kecil" style="margin:-6px 0 14px">
        Hitungan ini hanya terlihat oleh admin dan panitia. Siswa baru dapat melihat hasil setelah pemilihan ditutup.
    </p>

    @php $total = max(1, (int) ($kandidat->sum('jumlah_suara'))); @endphp

    @forelse ($kandidat as $k)
        @php $persen = ((int) $k->jumlah_suara) * 100 / $total; @endphp
        <div style="margin-bottom:16px" data-suarakandidat="{{ $k->id_kandidat }}:{{ (int) $k->jumlah_suara }}">
            <div class="meta" style="margin-bottom:6px">
                <span>
                    <span class="nomor">No. {{ str_pad((string) $k->nomor_urut, 2, '0', STR_PAD_LEFT) }}</span>
                    <strong>{{ $k->nama_ketua }}</strong>@if ($k->nama_wakil) &amp; {{ $k->nama_wakil }}@endif
                </span>
                <span><strong>{{ (int) $k->jumlah_suara }}</strong> suara ({{ number_format($persen, 1) }}%)</span>
            </div>
            <div class="bar"><span style="width:{{ $persen }}%"></span></div>
        </div>
    @empty
        <div class="kosong">Belum ada kandidat.</div>
    @endforelse
</div>

<div class="kartu">
    <h2>Status Pemilihan</h2>
    <div class="aksi-baris">
        @if ($pemilihan->status === 'draf')
            <form method="POST" action="{{ route('kelola.pemilihan.buka', $id) }}">
                @csrf
                <button class="btn" type="submit">Buka Pemilihan</button>
            </form>
            <a class="btn garis" href="{{ route('kelola.pemilihan.ubah', $id) }}">Ubah Data</a>
        @elseif ($pemilihan->status === 'berlangsung')
            <form method="POST" action="{{ route('kelola.pemilihan.tutup', $id) }}">
                @csrf
                <button class="btn oranye" type="submit">Tutup Pemilihan</button>
            </form>
            <form method="POST" action="{{ route('kelola.pemilihan.kembalikan', $id) }}">
                @csrf
                <button class="btn abu" type="submit">Kembalikan ke Draf</button>
            </form>
        @else
            <span class="pill">Pemilihan sudah selesai. Hasil terbuka untuk siswa.</span>
            <a class="btn garis" href="{{ route('siswa.pemilihan.hasil', $id) }}">Lihat Hasil</a>
        @endif
    </div>
</div>
@endsection
