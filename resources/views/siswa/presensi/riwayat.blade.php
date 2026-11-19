@extends('layouts.presensi')
@section('judul', 'Riwayat Presensi')
@section('isi')
<h1>Riwayat Presensi</h1>
<p class="subjudul">Roster jadwal kelas Anda beserta status kehadiran per tanggal.</p>

<div class="kartu" id="ringkasan">
    <div class="grid grid-2">
        <div class="stat">
            <span class="angka">{{ number_format($ringkasan['persen'], 2) }}%</span>
            <span class="label">Persentase Kehadiran</span>
        </div>
        <div class="stat">
            <span class="angka">{{ $ringkasan['hadir'] }}</span>
            <span class="label">Hadir</span>
        </div>
    </div>
    <p class="kecil" style="margin:12px 0 0">
        Izin {{ $ringkasan['izin'] }} &middot; Sakit {{ $ringkasan['sakit'] }} &middot; Alpa {{ $ringkasan['alpa'] }}
    </p>
</div>

<div class="kartu" id="roster">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <h3 style="margin:0">Roster Sepekan</h3>
        <div class="aksi-baris" style="align-items:center">
            <a class="btn garis" href="{{ route('siswa.presensi.riwayat', ['pekan' => $pekanSebelum]) }}">&lsaquo; Pekan lalu</a>
            <span class="kecil">
                {{ $pekan->copy()->locale('id')->isoFormat('D MMM') }} - {{ $pekan->copy()->addDays(5)->locale('id')->isoFormat('D MMM YYYY') }}
            </span>
            <a class="btn garis" href="{{ route('siswa.presensi.riwayat', ['pekan' => $pekanBerikut]) }}">Pekan depan &rsaquo;</a>
        </div>
    </div>

    <div class="tabel-gulir">
        <table class="tabel-roster">
            <thead>
                <tr><th>Hari</th><th>Tanggal</th><th>Jam</th><th>Mata Pelajaran</th><th>Guru</th><th>Status</th></tr>
            </thead>
            <tbody>
            @forelse ($roster as $r)
                <tr>
                    <td data-label="Hari">{{ ucfirst($r->hari) }}</td>
                    <td data-label="Tanggal">{{ $r->tanggal->locale('id')->isoFormat('D MMM') }}</td>
                    <td data-label="Jam">{{ substr($r->jam_mulai, 0, 5) }} - {{ substr($r->jam_selesai, 0, 5) }}</td>
                    <td data-label="Mata Pelajaran">{{ $r->nama_mapel }}</td>
                    <td data-label="Guru">{{ $r->nama_guru }}</td>
                    <td data-label="Status">
                        @if ($r->status === '-')
                            <span class="kecil">-</span>
                        @else
                            <span class="badge {{ $r->status }}">{{ $r->status }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="kosong">Belum ada jadwal untuk kelas Anda.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="kecil" style="margin-bottom:0">
        Tanda <strong>-</strong> berarti guru belum membuka sesi pada tanggal tersebut.
        Status <strong>alpa</strong> diberikan otomatis saat guru menutup sesi.
    </p>
</div>
@endsection
