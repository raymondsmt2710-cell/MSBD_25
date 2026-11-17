@extends('layouts.admin')
@section('judul', 'Kelas')
@section('jalur', 'Akademik / Kelas')
@section('isi')
<div class="kepala">
    <div>
        <h1>Kelas & Wali Kelas</h1>
        <p class="subjudul">Kelas dibuat per tahun ajaran. Klik nama kelas untuk mengatur roster siswanya.</p>
    </div>
    <a class="btn" href="{{ route('admin.kelas.create', ['tahun' => $idTahun]) }}">+ Kelas</a>
</div>

<div class="kartu">
    @if ($tahunAjaran->isEmpty())
        <div class="kosong">Buat <a href="{{ route('admin.tahun-ajaran.index') }}"><strong>tahun ajaran</strong></a> terlebih dahulu.</div>
    @else
        @include('admin._pilih_tahun')

        @if ($kelas->isEmpty())
            <div class="kosong">Belum ada kelas pada tahun ajaran ini.</div>
        @else
            <table>
                <thead><tr><th>Kelas</th><th>Tingkat</th><th>Jurusan</th><th>Ruangan</th><th>Wali kelas</th><th>Siswa</th><th>Jadwal</th><th></th></tr></thead>
                <tbody>
                @foreach ($kelas as $k)
                    <tr>
                        <td><a href="{{ route('admin.kelas.show', $k->id_kelas) }}"><strong>{{ $k->nama_kelas }}</strong></a></td>
                        <td>{{ $k->tingkat }}</td>
                        <td>{{ $k->jurusan ?? '—' }}</td>
                        <td>{{ $k->ruangan ?? '—' }}</td>
                        <td>{!! $k->wali ? e($k->wali) : '<span class="badge kuning">belum ada</span>' !!}</td>
                        <td>{{ $k->jumlah_siswa }}</td>
                        <td><a href="{{ route('admin.jadwal.index', ['kelas' => $k->id_kelas]) }}">{{ $k->jumlah_jadwal }} jam</a></td>
                        <td>
                            <div class="aksi-baris">
                                <a class="btn garis kecil" href="{{ route('admin.kelas.show', $k->id_kelas) }}">Roster</a>
                                <a class="btn garis kecil" href="{{ route('admin.kelas.edit', $k->id_kelas) }}">Ubah</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @endif
</div>
@endsection
