@extends('layouts.app')
@section('judul', 'Presensi – Jadwal Mengajar')
@section('isi')
<div class="card">
    <h2>Jadwal Mengajar</h2>
    <table>
        <tr><th>Hari</th><th>Jam</th><th>Kelas</th><th>Mata Pelajaran</th><th>Sesi Hari Ini</th></tr>
        @forelse ($jadwal as $j)
            <tr @if ($j->hari === $hariIni) style="background:#eff6ff" @endif>
                <td>{{ ucfirst($j->hari) }}</td>
                <td>{{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}</td>
                <td>{{ $j->nama_kelas }}</td>
                <td>{{ $j->nama_mapel }}</td>
                <td>
                    @if ($j->id_sesi)
                        <a href="{{ route('guru.presensi.sesi', $j->id_sesi) }}">Buka ({{ $j->status_sesi }})</a>
                    @elseif ($j->hari === $hariIni)
                        <form method="POST" action="{{ route('guru.presensi.buka', $j->id_jadwal) }}">@csrf
                            <button class="btn" type="submit">Mulai Presensi</button>
                        </form>
                    @else — @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Belum ada jadwal.</td></tr>
        @endforelse
    </table>
</div>
@endsection
