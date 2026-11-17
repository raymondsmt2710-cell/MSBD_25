@extends('layouts.app')
@section('judul', 'Detail Anak')
@section('isi')
<p style="font-size:13px;margin:0 0 12px">
    <a href="{{ route('ortu.index') }}" style="color:#1e3a8a">&larr; Kembali ke daftar anak</a>
</p>

<div class="card">
    <h2 style="margin:0 0 4px">{{ $anak->nama_siswa }}</h2>
    <p style="margin:0;font-size:13px;color:#6b7280">
        NIS {{ $anak->nis }}{{ $anak->nisn ? ' &middot; NISN '.$anak->nisn : '' }}
    </p>
    <table style="margin-top:10px">
        <tr><th style="width:170px">Kelas</th><td>{{ $anak->nama_kelas ?? 'belum terdaftar' }}</td></tr>
        <tr><th>Tahun ajaran</th><td>{{ $anak->nama_tahun_ajaran ?? '—' }}</td></tr>
        <tr><th>Status siswa</th><td>{{ $anak->status_siswa }}</td></tr>
        <tr><th>Hubungan</th><td>{{ ucfirst($anak->hubungan) }}{{ $anak->is_kontak_utama ? ' (kontak utama)' : '' }}</td></tr>
    </table>
</div>

<div class="card">
    <h3 style="margin:0 0 8px">Ringkasan Kehadiran</h3>
    <p style="font-size:28px;margin:4px 0">{{ number_format($ringkasan['persen'], 2) }}%</p>
    <p style="margin:0;font-size:14px">
        Hadir {{ $ringkasan['hadir'] }} &middot; Izin {{ $ringkasan['izin'] }} &middot;
        Sakit {{ $ringkasan['sakit'] }} &middot; Alpa {{ $ringkasan['alpa'] }}
    </p>
</div>

<div class="card">
    <h3 style="margin:0 0 10px">Riwayat Presensi</h3>

    <form method="GET" action="{{ route('ortu.anak', $anak->id_siswa) }}"
          style="margin-bottom:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <label for="bulan" style="font-size:13px">Bulan</label>
        <input type="month" id="bulan" name="bulan" value="{{ $bulan }}">
        <button class="btn" type="submit">Saring</button>
        @if ($bulan)
            <a class="btn abu" href="{{ route('ortu.anak', $anak->id_siswa) }}"
               style="text-decoration:none">Reset</a>
        @endif
    </form>

    <table>
        <tr>
            <th>Tanggal</th><th>Mata Pelajaran</th><th>Jam</th>
            <th>Status</th><th>Metode</th><th>Catatan</th>
        </tr>
        @forelse ($riwayat as $r)
            <tr>
                <td>{{ $r->tanggal }}</td>
                <td>{{ $r->nama_mapel }}</td>
                <td>{{ substr($r->jam_mulai, 0, 5) }}–{{ substr($r->jam_selesai, 0, 5) }}</td>
                <td><span class="badge {{ $r->status }}">{{ $r->status }}</span></td>
                <td>{{ $r->metode }}</td>
                <td>{{ $r->catatan ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="color:#6b7280">
                    {{ $bulan ? 'Tidak ada presensi pada bulan ini.' : 'Belum ada data presensi.' }}
                </td>
            </tr>
        @endforelse
    </table>

    <div style="margin-top:12px">{{ $riwayat->links('vendor.pagination.sederhana') }}</div>
</div>
@endsection
