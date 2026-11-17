@extends('layouts.admin')
@section('judul', 'Pengurus')
@section('jalur', 'Organisasi / Pengurus')
@section('isi')
<h1>Pengurus Kelas & Organisasi</h1>
<p class="subjudul">Tetapkan siswa pada jabatan per tahun ajaran. Jabatan diatur di <a href="{{ route('admin.jabatan.index') }}"><strong>Master Jabatan</strong></a>.</p>

@if ($tahunAjaran->isNotEmpty())
    <div class="kartu">@include('admin._pilih_tahun')</div>
@endif

<div class="grid grid-2">
    <div class="kartu">
        <h2>Pengurus kelas</h2>
        @if ($kelas->isEmpty())
            <div class="kosong">Belum ada kelas pada tahun ajaran ini.</div>
        @else
            <table>
                <thead><tr><th>Kelas</th><th>Pengurus</th><th>Bendahara</th><th></th></tr></thead>
                <tbody>
                @foreach ($kelas as $k)
                    <tr>
                        <td><strong>{{ $k->nama_kelas }}</strong></td>
                        <td>{{ $k->jumlah_pengurus }}</td>
                        <td>{!! $k->bendahara ? e($k->bendahara) : '<span class="badge kuning">belum ada</span>' !!}</td>
                        <td><a class="btn garis kecil" href="{{ route('admin.pengurus.kelas', $k->id_kelas) }}">Atur</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="kartu">
        <h2>Pengurus organisasi</h2>
        @if ($organisasi->isEmpty())
            <div class="kosong">Belum ada organisasi.</div>
        @else
            <table>
                <thead><tr><th>Organisasi</th><th>Pengurus</th><th></th></tr></thead>
                <tbody>
                @foreach ($organisasi as $o)
                    <tr>
                        <td><strong>{{ $o->singkatan ?: $o->nama_organisasi }}</strong></td>
                        <td>{{ $o->jumlah_pengurus }}</td>
                        <td><a class="btn garis kecil" href="{{ route('admin.pengurus.organisasi', [$o->id_organisasi, 'tahun' => $idTahun]) }}">Atur</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
