@extends('layouts.admin')
@section('judul', 'Detail Sesi Presensi')
@section('jalur', 'Pemantauan / Presensi / Sesi')
@section('isi')
<div class="kepala">
    <div>
        <h1>{{ $info->nama_kelas }} · {{ $info->nama_mapel }}</h1>
        <p class="subjudul">
            {{ \Illuminate\Support\Carbon::parse($info->tanggal)->translatedFormat('l, d F Y') }}
            · {{ substr($info->jam_mulai, 0, 5) }}–{{ substr($info->jam_selesai, 0, 5) }}
            · dibuka oleh {{ $info->nama_guru ?? '—' }} · sesi {{ $info->status }}
        </p>
    </div>
</div>

<div class="kartu">
    <table>
        <thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>Status</th><th>Metode</th><th>Waktu</th><th>Catatan</th></tr></thead>
        <tbody>
        @foreach ($siswa as $s)
            <tr>
                <td>{{ $s->no_absen ?? '—' }}</td>
                <td class="kecil">{{ $s->nis }}</td>
                <td>{{ $s->nama_siswa }}</td>
                <td>@if ($s->status)<span class="badge {{ $s->status }}">{{ $s->status }}</span>@else<span class="kecil">belum tercatat</span>@endif</td>
                <td class="kecil">{{ $s->metode ?? '' }}{{ $s->diubah_oleh ? ' · oleh '.$s->diubah_oleh : '' }}</td>
                <td class="kecil">{{ $s->waktu_presensi ? \Illuminate\Support\Carbon::parse($s->waktu_presensi)->format('H:i') : '' }}</td>
                <td class="kecil">{{ $s->catatan }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
