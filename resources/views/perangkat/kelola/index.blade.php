@extends('layouts.pemilihan')
@section('judul', 'Kelola Perangkat')
@section('isi')
@php
    // User::$role di-cast ke App\Enums\Role, jadi bandingkan lewat ->value.
    $peranSaya = auth()->user()->role instanceof \App\Enums\Role
        ? auth()->user()->role->value
        : (string) auth()->user()->role;
    $sayaAdmin = $peranSaya === \App\Enums\Role::Admin->value;
@endphp
<h1>Kelola Perangkat</h1>
<p class="subjudul">
    @if ($sayaAdmin)
        Anda dapat mengelola semua kelas dan organisasi.
    @else
        Anda dapat mengelola kelas yang Anda bimbing dan organisasi yang Anda bina.
    @endif
</p>

<div class="kartu">
    <h2>Kelas</h2>

    @if ($kelas->isEmpty())
        <div class="kosong">
            Tidak ada kelas yang menjadi tanggung jawab Anda.
            @if (! $sayaAdmin)
                Hubungi admin untuk menetapkan Anda sebagai wali kelas.
            @endif
        </div>
    @else
        <table>
            <thead><tr><th>Kelas</th><th>Wali Kelas</th><th>Pengurus</th><th>Aksi</th></tr></thead>
            <tbody>
            @foreach ($kelas as $k)
                <tr>
                    <td><strong>{{ $k->nama_kelas }}</strong></td>
                    <td class="kecil">{{ $k->wali_kelas ?? '—' }}</td>
                    <td>{{ $k->jumlah_pengurus }}</td>
                    <td><a class="btn garis" href="{{ route('perangkat.kelola.kelas', $k->id_kelas) }}">Atur Pengurus</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="kartu">
    <h2>Organisasi</h2>

    @if ($organisasi->isEmpty())
        <div class="kosong">
            Tidak ada organisasi yang menjadi tanggung jawab Anda.
            @if (! $sayaAdmin)
                Hubungi admin untuk menetapkan Anda sebagai pembina.
            @endif
        </div>
    @else
        <table>
            <thead><tr><th>Organisasi</th><th>Pembina</th><th>Pengurus</th><th>Aksi</th></tr></thead>
            <tbody>
            @foreach ($organisasi as $o)
                <tr>
                    <td><strong>{{ $o->nama_organisasi }}</strong></td>
                    <td class="kecil">{{ $o->pembina ?? '—' }}</td>
                    <td>{{ $o->jumlah_pengurus }}</td>
                    <td><a class="btn garis" href="{{ route('perangkat.kelola.organisasi', $o->id_organisasi) }}">Atur Pengurus</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="kartu">
    <a class="btn garis" href="{{ route('perangkat.index') }}">Lihat Halaman Perangkat</a>
</div>
@endsection
