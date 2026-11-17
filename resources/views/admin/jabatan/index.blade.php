@extends('layouts.admin')
@section('judul', 'Master Jabatan')
@section('jalur', 'Data Master / Jabatan')
@section('isi')
@php $baru = ! $edit->exists; @endphp
<h1>Master Jabatan</h1>
<p class="subjudul">Jabatan berlingkup <strong>kelas</strong> dipakai pengurus kelas (mis. bendahara); berlingkup <strong>organisasi</strong> dipakai pengurus organisasi. Kuota = jumlah pemegang maksimum.</p>

<div class="grid" style="grid-template-columns:minmax(0,2fr) minmax(260px,1fr);align-items:start">
    <div class="kartu">
        @if ($jabatan->isEmpty())
            <div class="kosong">Belum ada jabatan.</div>
        @else
            <table>
                <thead><tr><th>Urutan</th><th>Jabatan</th><th>Lingkup</th><th>Kuota</th><th>Dipegang</th><th></th></tr></thead>
                <tbody>
                @foreach ($jabatan as $j)
                    <tr>
                        <td>{{ $j->urutan }}</td>
                        <td><strong>{{ $j->nama_jabatan }}</strong></td>
                        <td><span class="badge {{ $j->lingkup === 'kelas' ? 'biru' : 'kuning' }}">{{ $j->lingkup }}</span></td>
                        <td>{{ $j->kuota }}</td>
                        <td>{{ $j->dipakai }}</td>
                        <td>
                            <div class="aksi-baris">
                                <a class="btn garis kecil" href="{{ route('admin.jabatan.edit', $j->id_jabatan) }}">Ubah</a>
                                @unless ($j->dipakai)
                                    <form method="POST" action="{{ route('admin.jabatan.destroy', $j->id_jabatan) }}" data-konfirmasi="Hapus jabatan {{ $j->nama_jabatan }}?">
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
        <h2>{{ $baru ? 'Tambah jabatan' : 'Ubah '.$edit->nama_jabatan }}</h2>
        <form method="POST" action="{{ $baru ? route('admin.jabatan.store') : route('admin.jabatan.update', $edit->id_jabatan) }}">
            @csrf
            @unless ($baru) @method('PUT') @endunless
            <div class="bidang">
                <label for="nama_jabatan">Nama jabatan</label>
                <input id="nama_jabatan" name="nama_jabatan" value="{{ old('nama_jabatan', $edit->nama_jabatan) }}" placeholder="Bendahara" required maxlength="50">
            </div>
            <div class="bidang">
                <label for="lingkup">Lingkup</label>
                <select id="lingkup" name="lingkup" required>
                    @foreach (['kelas', 'organisasi'] as $l)
                        <option value="{{ $l }}" @selected(old('lingkup', $edit->lingkup) === $l)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-grid">
                <div class="bidang">
                    <label for="kuota">Kuota</label>
                    <input id="kuota" type="number" name="kuota" min="1" max="50" value="{{ old('kuota', $edit->kuota) }}" required>
                </div>
                <div class="bidang">
                    <label for="urutan">Urutan tampil</label>
                    <input id="urutan" type="number" name="urutan" min="0" max="255" value="{{ old('urutan', $edit->urutan) }}" required>
                </div>
            </div>
            <div class="aksi-baris">
                <button class="btn" type="submit">Simpan</button>
                @unless ($baru)<a class="btn garis" href="{{ route('admin.jabatan.index') }}">Batal</a>@endunless
            </div>
        </form>
    </div>
</div>
@endsection
