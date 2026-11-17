@extends('layouts.admin')
@section('judul', 'Pengajuan Izin')
@section('jalur', 'Pemantauan / Presensi / Pengajuan Izin')
@section('isi')
<div class="kepala">
    <div>
        <h1>Pengajuan Izin & Sakit</h1>
        <p class="subjudul">Hanya-baca. Verifikasi dilakukan guru/wali kelas.</p>
    </div>
    <a class="btn garis" href="{{ route('admin.presensi.index') }}">← Sesi presensi</a>
</div>

<div class="kartu">@include('admin.presensi._filter', ['denganStatus' => true])</div>

<div class="kartu">
    @if ($izin->isEmpty())
        <div class="kosong">Tidak ada pengajuan pada filter ini.</div>
    @else
        <table>
            <thead><tr><th>Siswa</th><th>Kelas</th><th>Jenis</th><th>Tanggal</th><th>Alasan</th><th>Status</th><th>Diverifikasi</th></tr></thead>
            <tbody>
            @foreach ($izin as $i)
                <tr>
                    <td>{{ $i->nama_siswa }}<br><span class="kecil">{{ $i->nis }}</span></td>
                    <td>{{ $i->nama_kelas }}</td>
                    <td><span class="badge {{ $i->jenis }}">{{ $i->jenis }}</span></td>
                    <td class="kecil">{{ \Illuminate\Support\Carbon::parse($i->tanggal_mulai)->format('d M') }}
                        @if ($i->tanggal_selesai !== $i->tanggal_mulai) – {{ \Illuminate\Support\Carbon::parse($i->tanggal_selesai)->format('d M') }}@endif</td>
                    <td class="kecil">{{ \Illuminate\Support\Str::limit($i->alasan, 80) }}
                        @if ($i->lampiran_url) · <a href="{{ str_starts_with($i->lampiran_url, 'http') ? $i->lampiran_url : asset('storage/'.$i->lampiran_url) }}" target="_blank" rel="noopener">lampiran</a>@endif</td>
                    <td><span class="badge {{ $i->status }}">{{ $i->status }}</span></td>
                    <td class="kecil">{{ $i->verifikator ?? '—' }}{{ $i->diverifikasi_pada ? ' · '.\Illuminate\Support\Carbon::parse($i->diverifikasi_pada)->format('d M H:i') : '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="paginasi">{{ $izin->links() }}</div>
    @endif
</div>
@endsection
