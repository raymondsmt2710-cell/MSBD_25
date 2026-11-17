@extends('layouts.admin')
@section('judul', 'Akun Pengguna')
@section('jalur', 'Akun / Akun Pengguna')
@section('isi')
<div class="kepala">
    <div>
        <h1>Akun Pengguna</h1>
        <p class="subjudul">Buat akun, atur peran, aktifkan/nonaktifkan, dan reset password.</p>
    </div>
    <a class="btn" href="{{ route('admin.akun.create') }}">+ Akun Baru</a>
</div>

@include('admin._sandi')

<div class="kartu">
    <form method="GET" class="filter">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari username / nama">
        <select name="role">
            <option value="">Semua peran</option>
            @foreach ($roles as $r)
                <option value="{{ $r }}" @selected(request('role') === $r)>{{ str_replace('_', ' ', $r) }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">Semua status</option>
            <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
        </select>
        <button class="btn garis" type="submit">Terapkan</button>
    </form>

    @if ($akun->isEmpty())
        <div class="kosong">Tidak ada akun yang cocok.</div>
    @else
        <table>
            <thead><tr><th>Username</th><th>Peran</th><th>Nama profil</th><th>Status</th><th>Login terakhir</th><th>Aksi</th></tr></thead>
            <tbody>
            @foreach ($akun as $a)
                <tr class="{{ $a->is_active ? '' : 'redup' }}">
                    <td><strong>{{ $a->username }}</strong></td>
                    <td><span class="badge {{ $a->role === 'admin' ? 'biru' : '' }}">{{ str_replace('_', ' ', $a->role) }}</span></td>
                    <td>
                        @if ($a->nama_profil)
                            {{ $a->nama_profil }}
                        @elseif ($a->role !== 'admin')
                            @php $rute = ['siswa' => 'admin.siswa.create', 'guru' => 'admin.guru.create', 'orang_tua' => 'admin.orang-tua.create'][$a->role]; @endphp
                            <a class="btn oranye kecil" href="{{ route($rute, ['id_user' => $a->id_user]) }}">Lengkapi profil</a>
                        @else
                            <span class="kecil">—</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $a->is_active ? 'aktif' : 'nonaktif' }}">{{ $a->is_active ? 'aktif' : 'nonaktif' }}</span></td>
                    <td class="kecil">{{ $a->last_login ? \Illuminate\Support\Carbon::parse($a->last_login)->format('d M Y H:i') : 'belum pernah' }}</td>
                    <td>
                        <div class="aksi-baris">
                            <a class="btn garis kecil" href="{{ route('admin.akun.edit', $a->id_user) }}">Ubah</a>
                            @if ($a->id_user !== auth()->id())
                                <form method="POST" action="{{ route('admin.akun.status', $a->id_user) }}"
                                      data-konfirmasi="{{ $a->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $a->username }}?">
                                    @csrf
                                    <button class="btn kecil {{ $a->is_active ? 'merah' : '' }}" type="submit">
                                        {{ $a->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.akun.reset', $a->id_user) }}"
                                  data-konfirmasi="Reset password {{ $a->username }}? Password lama tidak berlaku lagi.">
                                @csrf
                                <button class="btn abu kecil" type="submit">Reset password</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="paginasi">{{ $akun->links() }}</div>
    @endif
</div>
@endsection
