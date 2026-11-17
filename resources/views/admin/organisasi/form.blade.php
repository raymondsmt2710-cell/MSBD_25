@extends('layouts.admin')
@php $baru = ! $organisasi->exists; @endphp
@section('judul', $baru ? 'Organisasi Baru' : 'Ubah Organisasi')
@section('jalur', 'Organisasi / '.($baru ? 'Baru' : $organisasi->nama_organisasi))
@section('isi')
<h1>{{ $baru ? 'Organisasi Baru' : $organisasi->nama_organisasi }}</h1>
<p class="subjudul">Pembina adalah guru yang mendampingi organisasi.</p>

<div class="kartu" style="max-width:680px">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $baru ? route('admin.organisasi.store') : route('admin.organisasi.update', $organisasi->id_organisasi) }}">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        <div class="form-grid">
            <div class="bidang" style="grid-column:span 2">
                <label for="nama_organisasi">Nama organisasi</label>
                <input id="nama_organisasi" name="nama_organisasi" value="{{ old('nama_organisasi', $organisasi->nama_organisasi) }}" required maxlength="100">
            </div>
            <div class="bidang">
                <label for="singkatan">Singkatan</label>
                <input id="singkatan" name="singkatan" value="{{ old('singkatan', $organisasi->singkatan) }}" placeholder="OSIS" maxlength="20">
            </div>
            <div class="bidang">
                <label for="id_pembina">Pembina</label>
                <select id="id_pembina" name="id_pembina">
                    <option value="">— belum ditentukan —</option>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id_guru }}" @selected((int) old('id_pembina', $organisasi->id_pembina) === $g->id_guru)>{{ $g->nama_guru }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="bidang">
            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="4">{{ old('deskripsi', $organisasi->deskripsi) }}</textarea>
        </div>
        <div class="bidang">
            <label for="logo">Logo (jpg/png/webp/svg, ≤ 2 MB)</label>
            <input id="logo" type="file" name="logo" accept="image/*">
            @if (! $baru && $organisasi->logo_url)
                <label style="display:flex;gap:6px;align-items:center;font-weight:500;margin-top:6px">
                    <input type="checkbox" name="hapus_logo" value="1"> Hapus logo saat ini
                </label>
            @endif
        </div>
        <div class="aksi-baris">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn garis" href="{{ route('admin.organisasi.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection
