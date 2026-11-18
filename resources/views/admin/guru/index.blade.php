@extends('layouts.admin')
@section('judul', 'Data Guru')
@section('jalur', 'Data Master / Guru')
@section('isi')
<div class="kepala">
    <div>
        <h1>Data Guru</h1>
        <p class="subjudul">Profil guru dipakai untuk wali kelas, pengajar di jadwal, dan pembina organisasi.</p>
    </div>
    <a class="btn" href="{{ route('admin.guru.create') }}">+ Profil Guru</a>
</div>

<div class="kartu">
    <form method="GET" class="filter">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Nama / NIP">
        <button class="btn garis" type="submit">Cari</button>
    </form>

    @if ($guru->isEmpty())
        <div class="kosong">Belum ada data guru.</div>
    @else
        <table>
            <thead><tr><th>NIP</th><th>Nama</th><th>Kontak</th><th>Wali kelas (TA aktif)</th><th>Jadwal</th><th>Pembina</th><th></th></tr></thead>
            <tbody>
            @foreach ($guru as $g)
                <tr class="{{ $g->is_active ? '' : 'redup' }}">
                    <td class="kecil">{{ $g->nip }}</td>
                    <td><strong>{{ $g->nama_guru }}</strong><br><span class="kecil">{{ $g->username }}{{ $g->is_active ? '' : ' · akun nonaktif' }}</span></td>
                    <td class="kecil">{{ $g->no_hp ?? '—' }}<br>{{ $g->email }}</td>
                    <td>{{ $g->wali_dari ?? '—' }}</td>
                    <td>{{ $g->jumlah_jadwal }} jam</td>
                    <td>{{ $g->pembina_dari ?? '—' }}</td>
                    <td><a class="btn garis kecil" href="{{ route('admin.guru.edit', $g->id_guru) }}">Ubah</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="paginasi">{{ $guru->links() }}</div>
    @endif
</div>
@endsection
