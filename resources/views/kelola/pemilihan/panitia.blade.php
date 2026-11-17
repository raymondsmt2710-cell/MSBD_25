@extends('layouts.pemilihan')
@section('judul', 'Panitia Pemilihan')
@section('isi')
@php $id = $pemilihan->id_pemilihan; $draf = $pemilihan->status === 'draf'; @endphp

<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <h1>Panitia Pemilihan</h1>
        <p class="subjudul">{{ $pemilihan->judul }} — hanya admin yang dapat mengubah daftar panitia.</p>
    </div>
    <a class="btn garis" href="{{ route('kelola.pemilihan.monitor', $id) }}">Kembali ke Monitor</a>
</div>

@if (! $draf)
    <div class="err">Pemilihan sudah dibuka. Daftar panitia terkunci.</div>
@endif

<div class="kartu">
    <h2>Panitia Saat Ini ({{ $panitia->count() }})</h2>
    @if ($panitia->isEmpty())
        <div class="kosong">Belum ada panitia. Hanya admin yang dapat mengelola pemilihan ini.</div>
    @else
        <table>
            <thead><tr><th>Username</th><th>Nama Guru</th>@if ($draf)<th>Aksi</th>@endif</tr></thead>
            <tbody>
            @foreach ($panitia as $p)
                <tr>
                    <td>{{ $p->username }}</td>
                    <td>{{ $p->nama_guru ?? '—' }}</td>
                    @if ($draf)
                        <td>
                            <form method="POST" action="{{ route('kelola.pemilihan.panitia.simpan', $id) }}">
                                @csrf
                                <input type="hidden" name="id_user" value="{{ $p->id_user }}">
                                <button class="btn merah" type="submit">Cabut</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

@if ($draf)
    <div class="kartu">
        <h2>Tunjuk Panitia</h2>
        <form method="POST" action="{{ route('kelola.pemilihan.panitia.simpan', $id) }}">
            @csrf
            <div style="margin-bottom:14px">
                <label for="id_user">Guru</label>
                <select id="id_user" name="id_user" required>
                    <option value="">— pilih guru —</option>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id_user }}">{{ $g->nama_guru }} ({{ $g->username }})</option>
                    @endforeach
                </select>
            </div>
            <button class="btn" type="submit">Tunjuk</button>
        </form>
    </div>
@endif
@endsection
