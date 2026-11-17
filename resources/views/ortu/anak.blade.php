@extends('layouts.pemilihan')
@section('judul', 'Detail Anak')
@section('isi')
<p style="font-size:13px;margin:0 0 14px">
    <a href="{{ route('ortu.index') }}" style="color:#09656F;font-weight:600">&larr; Kembali ke daftar anak</a>
</p>

<h1>{{ $anak->nama_siswa }}</h1>
<p class="subjudul">
    NIS {{ $anak->nis }}{{ $anak->nisn ? ' · NISN '.$anak->nisn : '' }}
</p>

<div class="kartu">
    <div class="grid grid-2">
        <div class="stat">
            <span class="angka">{{ number_format($ringkasan['persen'], 2) }}%</span>
            <span class="label">Kehadiran</span>
        </div>
        <div class="stat">
            <span class="angka">
                {{ $ringkasan['hadir'] + $ringkasan['izin'] + $ringkasan['sakit'] + $ringkasan['alpa'] }}
            </span>
            <span class="label">Total presensi tercatat</span>
        </div>
    </div>
</div>

<div class="kartu">
    <h2>Profil</h2>
    <table>
        <tr><th style="width:180px">Kelas</th><td>{{ $anak->nama_kelas ?? 'belum terdaftar' }}</td></tr>
        <tr><th>Tahun ajaran</th><td>{{ $anak->nama_tahun_ajaran ?? '—' }}</td></tr>
        <tr><th>Status siswa</th><td><span class="badge berlangsung">{{ $anak->status_siswa }}</span></td></tr>
        <tr>
            <th>Hubungan</th>
            <td>
                {{ ucfirst($anak->hubungan) }}
                @if ($anak->is_kontak_utama)
                    <span class="pill" style="margin-left:6px">Kontak utama</span>
                @endif
            </td>
        </tr>
    </table>
</div>

<div class="kartu">
    <h2>Ringkasan Kehadiran</h2>
    <p style="margin:0;font-size:13px">
        Hadir <strong>{{ $ringkasan['hadir'] }}</strong> &middot;
        Izin <strong>{{ $ringkasan['izin'] }}</strong> &middot;
        Sakit <strong>{{ $ringkasan['sakit'] }}</strong> &middot;
        Alpa <strong>{{ $ringkasan['alpa'] }}</strong>
    </p>
</div>

<div class="kartu">
    <h2>Riwayat Presensi</h2>

    <form method="GET" action="{{ route('ortu.anak', $anak->id_siswa) }}"
          style="margin-bottom:14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <label for="bulan" style="margin:0;font-size:12px">Bulan</label>
        {{-- input[type=month] di layout lebarnya 100%; di sini dibatasi agar tidak
             memenuhi baris saat berdampingan dengan tombol. --}}
        <input type="month" id="bulan" name="bulan" value="{{ $bulan }}" style="width:auto">
        <button class="btn" type="submit">Saring</button>
        @if ($bulan)
            <a class="btn abu" href="{{ route('ortu.anak', $anak->id_siswa) }}">Reset</a>
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
                <td colspan="6" style="color:#9ca3af;text-align:center;padding:24px 8px">
                    {{ $bulan ? 'Tidak ada presensi pada bulan ini.' : 'Belum ada data presensi.' }}
                </td>
            </tr>
        @endforelse
    </table>

    @if ($riwayat->hasPages())
        <div class="paginasi">{{ $riwayat->links('vendor.pagination.sederhana') }}</div>
    @endif
</div>
@endsection
