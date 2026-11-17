@extends('layouts.admin')
@php $baru = ! $orangTua->exists; @endphp
@section('judul', $baru ? 'Profil Orang Tua Baru' : 'Ubah Orang Tua')
@section('jalur', 'Data Master / Orang Tua / '.($baru ? 'Baru' : 'Ubah'))
@section('isi')
<h1>{{ $baru ? 'Profil Orang Tua Baru' : $orangTua->nama }}</h1>
<p class="subjudul">{{ $baru ? 'Setelah disimpan, Anda diarahkan untuk menghubungkan profil ini ke siswa.' : 'Akun: '.$username }}</p>

@include('admin._sandi')

<div class="kartu" style="max-width:640px">
    <form method="POST" action="{{ $baru ? route('admin.orang-tua.store') : route('admin.orang-tua.update', $orangTua->id_orang_tua) }}">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        @if ($baru)
            @include('admin._pilih_akun', ['role' => 'orang tua', 'terpilih' => $orangTua->id_user])
        @endif

        <div class="bidang">
            <label for="nama">Nama lengkap</label>
            <input id="nama" name="nama" value="{{ old('nama', $orangTua->nama) }}" required maxlength="100">
        </div>
        <div class="form-grid">
            <div class="bidang">
                <label for="no_hp">No. HP</label>
                <input id="no_hp" name="no_hp" value="{{ old('no_hp', $orangTua->no_hp) }}" maxlength="15" inputmode="tel">
            </div>
            <div class="bidang">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $orangTua->email) }}" maxlength="100">
            </div>
            <div class="bidang">
                <label for="pekerjaan">Pekerjaan</label>
                <input id="pekerjaan" name="pekerjaan" value="{{ old('pekerjaan', $orangTua->pekerjaan) }}" maxlength="50">
            </div>
        </div>
        <div class="bidang">
            <label for="alamat">Alamat</label>
            <textarea id="alamat" name="alamat" rows="2">{{ old('alamat', $orangTua->alamat) }}</textarea>
        </div>
        <div class="aksi-baris">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn garis" href="{{ $baru ? route('admin.orang-tua.index') : route('admin.orang-tua.show', $orangTua->id_orang_tua) }}">Batal</a>
        </div>
    </form>
</div>
@endsection
