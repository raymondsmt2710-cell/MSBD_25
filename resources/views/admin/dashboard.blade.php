@extends('layouts.admin')
@section('judul', 'Dasbor')
@section('jalur', 'Dasbor')
@section('isi')
<h1>Dasbor Administrator</h1>
<p class="subjudul">
    Tahun ajaran aktif:
    @if ($tahunAktif)
        <strong>{{ $tahunAktif->nama }}</strong>
    @else
        <a href="{{ route('admin.tahun-ajaran.index') }}" style="color:#b91c1c;font-weight:600">belum ditentukan — atur sekarang</a>
    @endif
</p>

@foreach ($peringatan as $p)
    <div class="info">{{ $p }}</div>
@endforeach

<div class="grid grid-4" style="margin-bottom:16px">
    @foreach ($statistik as $label => $angka)
        <div class="kartu stat" style="margin:0">
            <span class="angka">{{ number_format($angka) }}</span>
            <span class="label">{{ $label }}</span>
        </div>
    @endforeach
</div>

<div class="grid grid-2">
    <div class="kartu">
        <h2>Akun per peran</h2>
        <table>
            <thead><tr><th>Peran</th><th>Aktif</th><th>Total</th></tr></thead>
            <tbody>
            @foreach (['admin', 'guru', 'siswa', 'orang_tua'] as $role)
                <tr>
                    <td><a href="{{ route('admin.akun.index', ['role' => $role]) }}">{{ str_replace('_', ' ', $role) }}</a></td>
                    <td>{{ (int) ($akun[$role]->aktif ?? 0) }}</td>
                    <td>{{ (int) ($akun[$role]->total ?? 0) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="kartu">
        <h2>Pintasan</h2>
        <div class="aksi-baris">
            <a class="btn" href="{{ route('admin.akun.create') }}">+ Akun baru</a>
            <a class="btn garis" href="{{ route('admin.kelas.create') }}">+ Kelas</a>
            <a class="btn garis" href="{{ route('admin.jadwal.index') }}">Susun jadwal</a>
            <a class="btn garis" href="{{ route('kelola.pemilihan.form') }}">+ Pemilihan</a>
        </div>
        <p class="kecil" style="margin-top:14px">
            Pengajuan izin menunggu verifikasi guru:
            <a href="{{ route('admin.presensi.izin', ['status' => 'menunggu']) }}"><strong>{{ $izinMenunggu }}</strong></a>
        </p>
    </div>
</div>
@endsection
