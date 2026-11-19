@extends('layouts.presensi')
@section('judul', 'Presensi')
@section('isi')
<h1>Presensi</h1>
<p class="subjudul">Pilih menu yang ingin dibuka.</p>

<div class="pilihan">
    <a class="pilihan-kartu" href="{{ route('siswa.presensi.riwayat') }}">
        <span class="pilihan-ikon"><x-ikon nama="calendar-week" ukuran="22" /></span>
        <h2 class="pilihan-judul">Riwayat Presensi</h2>
        <p class="pilihan-ket">
            Lihat roster jadwal sepekan beserta status kehadiran Anda: hadir, izin, sakit, atau alpa.
        </p>
    </a>

    <a class="pilihan-kartu oranye" href="{{ route('siswa.presensi.pindai') }}">
        <span class="pilihan-ikon"><x-ikon nama="qr-code-scan" ukuran="22" /></span>
        <h2 class="pilihan-judul">Pindai QR</h2>
        <p class="pilihan-ket">
            Pindai QR di layar guru dengan kamera. Kamera rusak? Pilih foto QR dari galeri.
        </p>
    </a>
</div>

<div class="kartu" style="margin-top:16px">
    <div class="grid grid-2">
        <div class="stat">
            <span class="angka">{{ number_format($ringkasan['persen'], 2) }}%</span>
            <span class="label">Persentase Kehadiran</span>
        </div>
        <div class="stat">
            <span class="angka">{{ $ringkasan['hadir'] }}</span>
            <span class="label">Hadir</span>
        </div>
    </div>
    <p class="kecil" style="margin:12px 0 0">
        Izin {{ $ringkasan['izin'] }} &middot; Sakit {{ $ringkasan['sakit'] }} &middot; Alpa {{ $ringkasan['alpa'] }}
    </p>
</div>
@endsection
