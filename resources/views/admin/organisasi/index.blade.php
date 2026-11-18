@extends('layouts.admin')
@section('judul', 'Organisasi Sekolah')
@section('jalur', 'Organisasi / Organisasi Sekolah')
@section('isi')
<div class="kepala">
    <div>
        <h1>Organisasi Sekolah</h1>
        <p class="subjudul">OSIS, MPK, ekstrakurikuler, dan organisasi lain beserta guru pembinanya.</p>
    </div>
    <a class="btn" href="{{ route('admin.organisasi.create') }}">+ Organisasi</a>
</div>

<div class="kartu">
    @if ($organisasi->isEmpty())
        <div class="kosong">Belum ada organisasi.</div>
    @else
        <table>
            <thead><tr><th></th><th>Organisasi</th><th>Pembina</th><th>Pengurus (TA aktif)</th><th>Pemilihan</th><th></th></tr></thead>
            <tbody>
            @foreach ($organisasi as $o)
                <tr>
                    <td style="width:48px">
                        @if ($o->logo_url)
                            <img src="{{ str_starts_with($o->logo_url, 'http') ? $o->logo_url : asset('storage/'.$o->logo_url) }}" alt="" style="width:36px;height:36px;object-fit:contain;border-radius:8px">
                        @endif
                    </td>
                    <td><strong>{{ $o->nama_organisasi }}</strong>{{ $o->singkatan ? ' ('.$o->singkatan.')' : '' }}<br>
                        <span class="kecil">{{ \Illuminate\Support\Str::limit($o->deskripsi, 90) }}</span></td>
                    <td>{!! $o->pembina ? e($o->pembina) : '<span class="badge kuning">belum ada</span>' !!}</td>
                    <td><a href="{{ route('admin.pengurus.organisasi', $o->id_organisasi) }}">{{ $o->jumlah_pengurus }} orang</a></td>
                    <td>{{ $o->jumlah_pemilihan }}</td>
                    <td>
                        <div class="aksi-baris">
                            <a class="btn garis kecil" href="{{ route('admin.organisasi.edit', $o->id_organisasi) }}">Ubah</a>
                            @if (! $o->jumlah_pemilihan && ! $o->jumlah_pengurus)
                                <form method="POST" action="{{ route('admin.organisasi.destroy', $o->id_organisasi) }}" data-konfirmasi="Hapus {{ $o->nama_organisasi }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn merah kecil" type="submit">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
