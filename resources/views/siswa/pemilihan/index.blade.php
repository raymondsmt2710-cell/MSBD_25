@extends('layouts.pemilihan')
@section('judul', 'Pemilihan')
@section('isi')
<h1>Pemilihan</h1>
<p class="subjudul">Pemilihan yang Anda ikuti. Suara Anda rahasia dan tidak dapat diubah setelah dikirim.</p>

@forelse ($pemilihan as $p)
    @php
        $bagian = $partisipasi[$p->id_pemilihan] ?? null;
        $bisaMemilih = \App\Models\Pemilihan::bolehMemilih($p->id_pemilihan, auth()->user()->siswa->id_siswa);
    @endphp
    <div class="kartu">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div>
                <h2 style="margin-bottom:6px">{{ $p->judul }}</h2>
                <div class="kecil" style="margin-bottom:8px">
                    {{ $p->nama_organisasi }} ·
                    {{ \Illuminate\Support\Carbon::parse($p->waktu_mulai)->format('d M Y H:i') }} s/d
                    {{ \Illuminate\Support\Carbon::parse($p->waktu_selesai)->format('d M Y H:i') }}
                </div>
                <span class="badge {{ $p->status }}">{{ $p->status }}</span>
                @if ($p->sudah_memilih)
                    <span class="badge berlangsung">✓ Sudah memilih</span>
                @endif
            </div>
            <div class="aksi-baris">
                @if ($bisaMemilih)
                    <a class="btn" href="{{ route('siswa.pemilihan.pilih', $p->id_pemilihan) }}">Pilih Sekarang</a>
                @elseif ($p->status === 'selesai')
                    <a class="btn oranye" href="{{ route('siswa.pemilihan.hasil', $p->id_pemilihan) }}">Lihat Hasil</a>
                @elseif ($p->sudah_memilih)
                    <span class="pill">Terima kasih, suara Anda sudah tercatat</span>
                @elseif ($p->status === 'draf')
                    <span class="pill">Menunggu dibuka panitia</span>
                @else
                    <span class="pill">Pemungutan suara sudah berakhir</span>
                @endif
            </div>
        </div>

        @if ($bagian)
            <div style="margin-top:16px">
                <div class="meta" style="margin-bottom:6px">
                    <span>Partisipasi pemilih</span>
                    <span><strong>{{ $bagian->sudah_memilih }}</strong> dari {{ $bagian->total_pemilih }}
                        ({{ number_format((float) $bagian->persentase, 1) }}%)</span>
                </div>
                <div class="bar"><span style="width:{{ (float) $bagian->persentase }}%"></span></div>
            </div>
        @endif
    </div>
@empty
    <div class="kartu">
        <div class="kosong">Belum ada pemilihan yang Anda ikuti.</div>
    </div>
@endforelse

{{-- Jalan pulang yang pasti. Tombol kembali di atas mengikuti riwayat browser,
     sehingga setelah mengirim suara ia bisa menunjuk ke halaman pilih yang
     sudah tidak boleh dibuka lagi. Tombol ini selalu ke beranda siswa. --}}
<div class="kartu">
    <a class="btn garis" href="{{ route('siswa.beranda') }}">
        <x-ikon nama="arrow-left" ukuran="15" /> Kembali ke Dashboard
    </a>
</div>
@endsection
