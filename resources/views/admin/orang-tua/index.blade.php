@extends('layouts.admin')
@section('judul', 'Orang Tua / Wali')
@section('jalur', 'Data Master / Orang Tua')
@section('isi')
<div class="kepala">
    <div>
        <h1>Orang Tua / Wali</h1>
        <p class="subjudul">Relasi ke siswa menentukan anak mana yang dapat dipantau di portal orang tua.</p>
    </div>
    <a class="btn" href="{{ route('admin.orang-tua.create') }}">+ Profil Orang Tua</a>
</div>

<div class="kartu">
    <form method="GET" class="filter">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Nama orang tua / nama atau NIS anak">
        <button class="btn garis" type="submit">Cari</button>
    </form>

    @if ($orangTua->isEmpty())
        <div class="kosong">Belum ada data orang tua.</div>
    @else
        <table>
            <thead><tr><th>Nama</th><th>Kontak</th><th>Anak terhubung</th><th></th></tr></thead>
            <tbody>
            @foreach ($orangTua as $o)
                <tr class="{{ $o->is_active ? '' : 'redup' }}">
                    <td><strong>{{ $o->nama }}</strong><br><span class="kecil">{{ $o->username }}</span></td>
                    <td class="kecil">{{ $o->no_hp ?? '—' }}<br>{{ $o->email }}</td>
                    <td>
                        @if ($o->anak)
                            {{ $o->anak }}
                        @else
                            <span class="badge kuning">belum terhubung</span>
                        @endif
                    </td>
                    <td><a class="btn garis kecil" href="{{ route('admin.orang-tua.show', $o->id_orang_tua) }}">Kelola</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="paginasi">{{ $orangTua->links() }}</div>
    @endif
</div>
@endsection
