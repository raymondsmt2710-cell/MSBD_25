@extends('layouts.siswa')

@section('judul', 'Beranda Siswa')

@section('isi')

{{-- ---------- Hero ---------- --}}
<div class="hero">
    <div class="hero-inner">
        <div class="lencana">
            <span class="dot"></span>
            Beranda Siswa
        </div>
        <h1>Selamat datang, <span>{{ $siswa->nama_siswa }}</span></h1>
        <p>
            NIS {{ $siswa->nis }}
            <span class="pemisah">·</span> Kelas {{ $kelas }}
            <span class="pemisah">·</span> Status {{ $siswa->status }}
        </p>
    </div>
</div>

{{-- ---------- Statistik kehadiran ---------- --}}
<div class="statistik">
    <div class="statistik-grid">
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="check2-square" ukuran="19" /></div>
            <div class="angka">{{ number_format($ringkasan['persen'], 2) }}%</div>
            <div class="label">Kehadiran</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="check-lg" ukuran="19" /></div>
            <div class="angka">{{ $ringkasan['hadir'] }}</div>
            <div class="label">Hadir</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="calendar-week" ukuran="19" /></div>
            <div class="angka">{{ $ringkasan['izin'] }}</div>
            <div class="label">Izin</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="person" ukuran="19" /></div>
            <div class="angka">{{ $ringkasan['sakit'] }}</div>
            <div class="label">Sakit</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="exclamation-triangle" ukuran="19" /></div>
            <div class="angka">{{ $ringkasan['alpa'] }}</div>
            <div class="label">Alpa</div>
        </div>
    </div>
</div>

@endsection
