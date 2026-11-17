@extends('layouts.admin')
@php $baru = ! $guru->exists; @endphp
@section('judul', $baru ? 'Profil Guru Baru' : 'Ubah Guru')
@section('jalur', 'Data Master / Guru / '.($baru ? 'Baru' : 'Ubah'))
@section('isi')
<h1>{{ $baru ? 'Profil Guru Baru' : $guru->nama_guru }}</h1>
<p class="subjudul">{{ $baru ? 'Profil guru ditempelkan ke akun ber-peran guru.' : 'Akun: '.$username }}</p>

@include('admin._sandi')

<div class="kartu" style="max-width:640px">
    <form method="POST" action="{{ $baru ? route('admin.guru.store') : route('admin.guru.update', $guru->id_guru) }}">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        @if ($baru)
            @include('admin._pilih_akun', ['role' => 'guru', 'terpilih' => $guru->id_user])
        @endif

        <div class="form-grid">
            <div class="bidang">
                <label for="nip">NIP</label>
                <input id="nip" name="nip" value="{{ old('nip', $guru->nip) }}" required maxlength="20" inputmode="numeric">
            </div>
            <div class="bidang">
                <label for="jenis_kelamin">Jenis kelamin</label>
                <select id="jenis_kelamin" name="jenis_kelamin" required>
                    <option value="L" @selected(old('jenis_kelamin', $guru->jenis_kelamin) === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('jenis_kelamin', $guru->jenis_kelamin) === 'P')>Perempuan</option>
                </select>
            </div>
        </div>
        <div class="bidang">
            <label for="nama_guru">Nama lengkap (dengan gelar)</label>
            <input id="nama_guru" name="nama_guru" value="{{ old('nama_guru', $guru->nama_guru) }}" required maxlength="100">
        </div>
        <div class="form-grid">
            <div class="bidang">
                <label for="no_hp">No. HP</label>
                <input id="no_hp" name="no_hp" value="{{ old('no_hp', $guru->no_hp) }}" maxlength="15" inputmode="tel">
            </div>
            <div class="bidang">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $guru->email) }}" maxlength="100">
            </div>
        </div>
        <div class="aksi-baris">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn garis" href="{{ route('admin.guru.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection
