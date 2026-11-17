@extends('layouts.admin')
@section('judul', 'Denah Semua Kelas')
@section('jalur', 'Pemantauan / Denah')
@section('isi')
<h1>Denah Semua Kelas</h1>
<p class="subjudul">Setiap kelas dapat punya beberapa versi denah (rotasi tempat duduk); satu yang aktif.</p>

@if ($tahunAjaran->isNotEmpty())
    <div class="kartu">@include('admin._pilih_tahun')</div>
@endif

<div class="kartu">
    @if ($kelas->isEmpty())
        <div class="kosong">Belum ada kelas pada tahun ajaran ini.</div>
    @else
        <table>
            <thead><tr><th>Kelas</th><th>Denah aktif</th><th>Berlaku mulai</th><th>Kursi terisi</th><th></th></tr></thead>
            <tbody>
            @foreach ($kelas as $k)
                <tr>
                    <td><strong>{{ $k->nama_kelas }}</strong></td>
                    <td>{!! $k->id_denah ? e($k->jumlah_baris.' × '.$k->jumlah_kolom) : '<span class="badge kuning">belum ada</span>' !!}</td>
                    <td>{{ $k->berlaku_mulai ? \Illuminate\Support\Carbon::parse($k->berlaku_mulai)->format('d M Y') : '—' }}</td>
                    <td>
                        @if ($k->id_denah)
                            {{ $k->terisi }} / {{ $k->jumlah_siswa }} siswa
                            @if ($k->terisi < $k->jumlah_siswa)<span class="badge kuning">{{ $k->jumlah_siswa - $k->terisi }} belum duduk</span>@endif
                        @endif
                    </td>
                    <td>
                        <div class="aksi-baris">
                            @if ($k->id_denah)<a class="btn kecil" href="{{ route('admin.denah.edit', $k->id_denah) }}">Atur kursi</a>@endif
                            <a class="btn garis kecil" href="{{ route('admin.denah.kelas', $k->id_kelas) }}">Versi denah</a>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
