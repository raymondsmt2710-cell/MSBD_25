@extends('layouts.admin')
@php $baru = ! $user->exists; @endphp
@section('judul', $baru ? 'Akun Baru' : 'Ubah Akun')
@section('jalur', 'Akun / '.($baru ? 'Akun Baru' : 'Ubah Akun'))
@section('isi')
<h1>{{ $baru ? 'Akun Baru' : 'Ubah Akun '.$user->username }}</h1>
<p class="subjudul">
    @if ($baru)
        Setelah akun siswa, guru, atau orang tua dibuat, Anda langsung diarahkan ke form profilnya.
    @else
        Password tidak diubah di sini — gunakan tombol Reset password di daftar akun.
    @endif
</p>

<div class="kartu" style="max-width:560px">
    <form method="POST" action="{{ $baru ? route('admin.akun.store') : route('admin.akun.update', $user->id_user) }}">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        <div class="bidang">
            <label for="username">Username</label>
            <input id="username" name="username" value="{{ old('username', $user->username) }}" required maxlength="50" autocomplete="off">
            <span class="kecil">Huruf, angka, titik, garis bawah, tanda hubung. Untuk siswa disarankan memakai NIS.</span>
        </div>

        <div class="bidang">
            <label for="role">Peran</label>
            <select id="role" name="role" required @disabled(! $baru && ($punyaProfil ?? false))>
                @foreach ($roles as $r)
                    <option value="{{ $r }}" @selected(old('role', $user->role?->value ?? $user->role) === $r)>{{ str_replace('_', ' ', $r) }}</option>
                @endforeach
            </select>
            @if (! $baru && ($punyaProfil ?? false))
                {{-- select yang disabled tidak ikut terkirim, jadi nilai lama dikirim lewat hidden. --}}
                <input type="hidden" name="role" value="{{ $user->role?->value ?? $user->role }}">
                <span class="kecil">Peran terkunci karena akun sudah memiliki profil.</span>
            @endif
        </div>

        @if ($baru)
            <div class="bidang">
                <label for="password">Password awal</label>
                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password">
                <span class="kecil">Kosongkan agar sistem membuat password acak yang ditampilkan sekali.</span>
            </div>

            <div class="bidang">
                <label style="display:flex;gap:8px;align-items:center;font-weight:500">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Akun langsung aktif
                </label>
            </div>
        @endif

        <div class="aksi-baris">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn garis" href="{{ route('admin.akun.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection
