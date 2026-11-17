@extends('layouts.admin')
@section('judul', 'Mata Pelajaran')
@section('jalur', 'Data Master / Mata Pelajaran')
@section('isi')
@php $baru = ! $edit->exists; @endphp
<h1>Mata Pelajaran</h1>
<p class="subjudul">Daftar mapel yang dipakai di jadwal pelajaran.</p>

<div class="grid" style="grid-template-columns:minmax(0,2fr) minmax(260px,1fr);align-items:start">
    <div class="kartu">
        @if ($mapel->isEmpty())
            <div class="kosong">Belum ada mata pelajaran.</div>
        @else
            <table>
                <thead><tr><th>Kode</th><th>Nama</th><th>Dipakai di jadwal</th><th></th></tr></thead>
                <tbody>
                @foreach ($mapel as $m)
                    <tr>
                        <td><span class="badge">{{ $m->kode_mapel }}</span></td>
                        <td>{{ $m->nama_mapel }}</td>
                        <td>{{ $m->jumlah_jadwal }}×</td>
                        <td>
                            <div class="aksi-baris">
                                <a class="btn garis kecil" href="{{ route('admin.mapel.edit', $m->id_mapel) }}">Ubah</a>
                                @unless ($m->jumlah_jadwal)
                                    <form method="POST" action="{{ route('admin.mapel.destroy', $m->id_mapel) }}" data-konfirmasi="Hapus mapel {{ $m->nama_mapel }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn merah kecil" type="submit">Hapus</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="kartu">
        <h2>{{ $baru ? 'Tambah mapel' : 'Ubah '.$edit->nama_mapel }}</h2>
        <form method="POST" action="{{ $baru ? route('admin.mapel.store') : route('admin.mapel.update', $edit->id_mapel) }}">
            @csrf
            @unless ($baru) @method('PUT') @endunless
            <div class="bidang">
                <label for="kode_mapel">Kode</label>
                <input id="kode_mapel" name="kode_mapel" value="{{ old('kode_mapel', $edit->kode_mapel) }}" placeholder="MTK" required maxlength="10" style="text-transform:uppercase">
            </div>
            <div class="bidang">
                <label for="nama_mapel">Nama</label>
                <input id="nama_mapel" name="nama_mapel" value="{{ old('nama_mapel', $edit->nama_mapel) }}" required maxlength="100">
            </div>
            <div class="aksi-baris">
                <button class="btn" type="submit">Simpan</button>
                @unless ($baru)<a class="btn garis" href="{{ route('admin.mapel.index') }}">Batal</a>@endunless
            </div>
        </form>
    </div>
</div>
@endsection
