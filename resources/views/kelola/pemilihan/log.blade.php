@extends('layouts.pemilihan')
@section('judul', 'Log Pemilihan')
@section('isi')
@php $id = $pemilihan->id_pemilihan; @endphp

<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <h1>Log Pemilihan</h1>
        <p class="subjudul">{{ $pemilihan->judul }}</p>
    </div>
</div>

<div class="kartu">
    <p class="kecil" style="margin:0 0 14px">
        Log ini mencatat perubahan status dan keikutsertaan pemilih. Log <strong>tidak pernah</strong>
        menyebut kandidat yang dipilih, sehingga kerahasiaan suara tetap terjaga.
    </p>

    @if ($log->isEmpty())
        <div class="kosong">Belum ada catatan.</div>
    @else
        <table>
            <thead><tr><th>Waktu</th><th>Aksi</th><th>Perubahan</th><th>Keterangan</th><th>Oleh</th></tr></thead>
            <tbody>
            @foreach ($log as $l)
                <tr>
                    <td class="kecil">{{ \Illuminate\Support\Carbon::parse($l->dibuat_pada)->format('d M Y H:i:s') }}</td>
                    <td><strong>{{ $l->aksi }}</strong></td>
                    <td class="kecil">{{ $l->status_lama ?? '—' }} → {{ $l->status_baru ?? '—' }}</td>
                    <td class="kecil">{{ $l->keterangan }}</td>
                    <td class="kecil">{{ $l->username ?? 'sistem' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="paginasi">{{ $log->links() }}</div>
    @endif
</div>
@endsection
