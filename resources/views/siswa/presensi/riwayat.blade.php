@extends('layouts.app')
@section('judul', 'Riwayat Presensi')
@section('isi')
<div class="card">
    <h2>Ringkasan Kehadiran</h2>
    <p style="font-size:28px;margin:4px 0">{{ number_format($ringkasan['persen'], 2) }}%</p>
    <p>Hadir {{ $ringkasan['hadir'] }} · Izin {{ $ringkasan['izin'] }} · Sakit {{ $ringkasan['sakit'] }} · Alpa {{ $ringkasan['alpa'] }}</p>
    <p style="font-size:13px;color:#6b7280">Untuk presensi, pindai QR di layar guru dengan kamera ponsel (pastikan sudah masuk ke akun ini).</p>
</div>
<div class="card">
    <h3>Riwayat</h3>
    <table>
        <tr><th>Tanggal</th><th>Mata Pelajaran</th><th>Jam</th><th>Status</th><th>Metode</th><th>Waktu</th></tr>
        @forelse ($riwayat as $r)
            <tr>
                <td>{{ $r->tanggal }}</td><td>{{ $r->nama_mapel }}</td>
                <td>{{ substr($r->jam_mulai, 0, 5) }}–{{ substr($r->jam_selesai, 0, 5) }}</td>
                <td><span class="badge {{ $r->status }}">{{ $r->status }}</span></td>
                <td>{{ $r->metode }}</td><td>{{ $r->waktu_presensi ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Belum ada riwayat.</td></tr>
        @endforelse
    </table>
    {{ $riwayat->links() }}
</div>
@endsection
