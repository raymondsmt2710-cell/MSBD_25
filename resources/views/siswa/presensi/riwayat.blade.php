@extends('layouts.app')
@section('judul', 'Roster Presensi')
@section('isi')
<div class="card" id="ringkasan">
    <h2>Ringkasan Kehadiran</h2>
    <p style="font-size:28px;margin:4px 0">{{ number_format($ringkasan['persen'], 2) }}%</p>
    <p>Hadir {{ $ringkasan['hadir'] }} &middot; Izin {{ $ringkasan['izin'] }} &middot; Sakit {{ $ringkasan['sakit'] }} &middot; Alpa {{ $ringkasan['alpa'] }}</p>
    <p style="font-size:13px;color:#6b7280">
        Untuk presensi, pindai QR di layar guru dengan kamera ponsel (pastikan sudah masuk ke akun ini).
    </p>
</div>

<div class="card" id="roster">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <h3 style="margin:0">Roster Presensi</h3>
        <div style="display:flex;gap:8px;align-items:center">
            <a class="btn abu" href="{{ route('siswa.presensi.riwayat', ['pekan' => $pekanSebelum]) }}">&lsaquo; Pekan lalu</a>
            <span style="font-size:13px;color:#374151">
                {{ $pekan->copy()->locale('id')->isoFormat('D MMM') }} - {{ $pekan->copy()->addDays(5)->locale('id')->isoFormat('D MMM YYYY') }}
            </span>
            <a class="btn abu" href="{{ route('siswa.presensi.riwayat', ['pekan' => $pekanBerikut]) }}">Pekan depan &rsaquo;</a>
        </div>
    </div>

    <table style="margin-top:12px">
        <tr><th>Hari</th><th>Tanggal</th><th>Jam</th><th>Mata Pelajaran</th><th>Guru</th><th>Status</th></tr>
        @forelse ($roster as $r)
            <tr>
                <td>{{ ucfirst($r->hari) }}</td>
                <td>{{ $r->tanggal->locale('id')->isoFormat('D MMM') }}</td>
                <td>{{ substr($r->jam_mulai, 0, 5) }} - {{ substr($r->jam_selesai, 0, 5) }}</td>
                <td>{{ $r->nama_mapel }}</td>
                <td>{{ $r->nama_guru }}</td>
                <td>
                    @if ($r->status === '-')
                        <span style="color:#9ca3af">-</span>
                    @elseif ($r->status === 'belum')
                        <span class="badge">belum</span>
                    @else
                        <span class="badge {{ $r->status }}">{{ $r->status }}</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6">Belum ada jadwal untuk kelas Anda.</td></tr>
        @endforelse
    </table>
    <p style="font-size:12px;color:#9ca3af;margin-bottom:0">
        Tanda <strong>-</strong> berarti guru belum membuka sesi pada tanggal tersebut.
        Status <strong>alpa</strong> diberikan otomatis saat guru menutup sesi.
    </p>
</div>
@endsection
