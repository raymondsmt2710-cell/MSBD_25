@extends('layouts.pemilihan')
@section('judul', 'Pengurus '.$kelas->nama_kelas)
@section('isi')
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <h1>{{ $kelas->nama_kelas }}</h1>
        <p class="subjudul">
            Wali kelas: <strong>{{ $kelas->wali_kelas ?? '—' }}</strong>
            · {{ $jumlahAnggota }} siswa
            @if ($kelas->tahun_ajaran) · {{ $kelas->tahun_ajaran }} @endif
        </p>
    </div>
    <a class="btn garis" href="{{ route('perangkat.index') }}">← Kembali</a>
</div>

<div class="kartu">
    <h2>Susunan Pengurus</h2>

    @if ($jabatan->isEmpty())
        <div class="kosong">Belum ada jabatan untuk kelas. Jalankan seeder jabatan terlebih dahulu.</div>
    @else
        <table>
            <thead>
            <tr><th style="width:60px">No</th><th>Jabatan</th><th>Nama</th><th>NIS</th></tr>
            </thead>
            <tbody>
            @foreach ($jabatan as $i => $j)
                @php $orang = $pengurus[$j->id_jabatan] ?? collect(); @endphp
                @if ($orang->isEmpty())
                    <tr>
                        <td class="kecil">{{ $i + 1 }}</td>
                        <td><strong>{{ $j->nama_jabatan }}</strong></td>
                        <td class="kecil" colspan="2"><em>belum diisi</em></td>
                    </tr>
                @else
                    @foreach ($orang as $p)
                        <tr>
                            <td class="kecil">{{ $i + 1 }}</td>
                            <td><strong>{{ $j->nama_jabatan }}</strong></td>
                            <td>{{ $p->nama_siswa }}</td>
                            <td class="kecil">{{ $p->nis }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
            </tbody>
        </table>

        @if ($pengurus->isEmpty())
            <p class="kecil" style="margin:14px 0 0">
                Susunan pengurus belum pernah diisi untuk kelas ini.
            </p>
        @endif
    @endif
</div>
@endsection
