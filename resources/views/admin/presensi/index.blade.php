@extends('layouts.admin')
@section('judul', 'Presensi Semua Kelas')
@section('jalur', 'Pemantauan / Presensi')
@section('isi')
<div class="kepala">
    <div>
        <h1>Presensi Semua Kelas</h1>
        <p class="subjudul">Hanya-baca. Membuka sesi, koreksi manual, dan verifikasi izin tetap dilakukan guru.</p>
    </div>
    <a class="btn garis" href="{{ route('admin.presensi.izin') }}">Pengajuan izin →</a>
</div>

<div class="kartu">@include('admin.presensi._filter')</div>

<div class="kartu">
    <h2>Rekap per kelas · {{ \Illuminate\Support\Carbon::parse($dari)->format('d M') }} – {{ \Illuminate\Support\Carbon::parse($sampai)->format('d M Y') }}</h2>
    @if ($rekapKelas->isEmpty())
        <div class="kosong">Belum ada presensi tercatat pada rentang ini.</div>
    @else
        <table>
            <thead><tr><th>Kelas</th><th>Sesi</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpa</th><th>% hadir</th></tr></thead>
            <tbody>
            @foreach ($rekapKelas as $r)
                @php $persen = $r->total ? round($r->hadir / $r->total * 100, 1) : 0; @endphp
                <tr>
                    <td><a href="{{ request()->fullUrlWithQuery(['kelas' => $r->id_kelas]) }}"><strong>{{ $r->nama_kelas }}</strong></a></td>
                    <td>{{ $r->sesi }}</td>
                    <td>{{ $r->hadir }}</td><td>{{ $r->izin }}</td><td>{{ $r->sakit }}</td><td>{{ $r->alpa }}</td>
                    <td><span class="badge {{ $persen >= 90 ? 'hijau' : ($persen >= 75 ? 'kuning' : 'merah') }}">{{ $persen }}%</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="kartu">
    <h2>Sesi presensi</h2>
    @if ($sesi->isEmpty())
        <div class="kosong">Tidak ada sesi pada filter ini.</div>
    @else
        <table>
            <thead><tr><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Dibuka oleh</th><th>Status</th><th>H / I / S / A</th><th>Belum tercatat</th><th></th></tr></thead>
            <tbody>
            @foreach ($sesi as $s)
                @php $tercatat = $s->hadir + $s->izin + $s->sakit + $s->alpa; @endphp
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($s->tanggal)->translatedFormat('D, d M') }}<br>
                        <span class="kecil">{{ substr($s->jam_mulai, 0, 5) }}–{{ substr($s->jam_selesai, 0, 5) }}</span></td>
                    <td>{{ $s->nama_kelas }}</td>
                    <td>{{ $s->nama_mapel }}</td>
                    <td>{{ $s->nama_guru ?? '—' }}</td>
                    <td><span class="badge {{ $s->status === 'dibuka' ? 'hijau' : '' }}">{{ $s->status }}</span></td>
                    <td>{{ (int) $s->hadir }} / {{ (int) $s->izin }} / {{ (int) $s->sakit }} / {{ (int) $s->alpa }}</td>
                    <td>{{ max(0, $s->jumlah_siswa - $tercatat) }}</td>
                    <td><a class="btn garis kecil" href="{{ route('admin.presensi.sesi', $s->id_sesi) }}">Detail</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="paginasi">{{ $sesi->links() }}</div>
    @endif
</div>
@endsection
