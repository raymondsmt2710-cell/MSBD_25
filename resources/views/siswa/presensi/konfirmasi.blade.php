@extends('layouts.app')
@section('judul', 'Konfirmasi Presensi')
@section('isi')
<div class="card" style="max-width:420px;margin:30px auto;text-align:center">
    @if ($valid)
        <h2>Konfirmasi Kehadiran</h2>
        <p>Tekan tombol di bawah untuk mencatat kehadiran Anda pada sesi ini.</p>
        <form method="POST" action="{{ route('siswa.presensi.scan', $kode) }}">@csrf
            <button class="btn" type="submit">Saya Hadir</button>
        </form>
    @else
        <h2>QR Tidak Berlaku</h2>
        <p>QR sudah kedaluwarsa, sudah dipakai, atau sesi sudah ditutup. Pindai QR yang baru di layar guru.</p>
        <a class="btn abu" href="{{ route('siswa.presensi.riwayat') }}">Lihat Riwayat</a>
    @endif
</div>
@endsection
