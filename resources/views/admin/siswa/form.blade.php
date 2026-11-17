@extends('layouts.admin')
@php $baru = ! $siswa->exists; @endphp
@section('judul', $baru ? 'Profil Siswa Baru' : 'Ubah Siswa')
@section('jalur', 'Data Master / Siswa / '.($baru ? 'Baru' : 'Ubah'))
@section('isi')
<h1>{{ $baru ? 'Profil Siswa Baru' : $siswa->nama_siswa }}</h1>
<p class="subjudul">{{ $baru ? 'Profil siswa ditempelkan ke akun ber-peran siswa yang sudah dibuat.' : 'Akun: '.$username }}</p>

@include('admin._sandi')

<div class="kartu" style="max-width:760px">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $baru ? route('admin.siswa.store') : route('admin.siswa.update', $siswa->id_siswa) }}">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        @if ($baru)
            @include('admin._pilih_akun', ['role' => 'siswa', 'terpilih' => $siswa->id_user])
        @endif

        <div class="form-grid">
            <div class="bidang">
                <label for="nis">NIS</label>
                <input id="nis" name="nis" value="{{ old('nis', $siswa->nis) }}" required maxlength="20">
            </div>
            <div class="bidang">
                <label for="nisn">NISN (10 digit)</label>
                <input id="nisn" name="nisn" value="{{ old('nisn', $siswa->nisn) }}" required inputmode="numeric" pattern="[0-9]{10}" maxlength="10">
            </div>
        </div>

        <div class="bidang">
            <label for="nama_siswa">Nama lengkap</label>
            <input id="nama_siswa" name="nama_siswa" value="{{ old('nama_siswa', $siswa->nama_siswa) }}" required maxlength="100">
        </div>

        <div class="form-grid">
            <div class="bidang">
                <label for="jenis_kelamin">Jenis kelamin</label>
                <select id="jenis_kelamin" name="jenis_kelamin" required>
                    <option value="L" @selected(old('jenis_kelamin', $siswa->jenis_kelamin) === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('jenis_kelamin', $siswa->jenis_kelamin) === 'P')>Perempuan</option>
                </select>
            </div>
            <div class="bidang">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    @foreach ($statusList as $st)
                        <option value="{{ $st }}" @selected(old('status', $siswa->status) === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bidang">
                <label for="tempat_lahir">Tempat lahir</label>
                <input id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir', $siswa->tempat_lahir) }}" maxlength="50">
            </div>
            <div class="bidang">
                <label for="tanggal_lahir">Tanggal lahir</label>
                <input id="tanggal_lahir" type="date" name="tanggal_lahir"
                       value="{{ old('tanggal_lahir', $siswa->tanggal_lahir ? \Illuminate\Support\Carbon::parse($siswa->tanggal_lahir)->format('Y-m-d') : '') }}">
            </div>
            <div class="bidang">
                <label for="no_hp">No. HP</label>
                <input id="no_hp" name="no_hp" value="{{ old('no_hp', $siswa->no_hp) }}" maxlength="15" inputmode="tel">
            </div>
            <div class="bidang">
                <label for="foto">Foto (jpg/png/webp, ≤ 2 MB)</label>
                <input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>

        <div class="bidang">
            <label for="alamat">Alamat</label>
            <textarea id="alamat" name="alamat" rows="2">{{ old('alamat', $siswa->alamat) }}</textarea>
        </div>

        <div class="aksi-baris">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn garis" href="{{ route('admin.siswa.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection
