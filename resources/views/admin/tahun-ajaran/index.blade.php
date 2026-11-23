@extends('layouts.admin')
@section('judul', 'Tahun Ajaran')
@section('jalur', 'Data Master / Tahun Ajaran')
@section('isi')
@php $baru = ! $edit->exists; @endphp
<h1>Tahun Ajaran</h1>
<p class="subjudul">Tepat satu tahun ajaran aktif. Kelas, pemilihan, dan kepengurusan mengikuti tahun ajaran aktif.</p>

<div class="grid grid-dua">
    <div class="kartu">
        @if ($tahunAjaran->isEmpty())
            <div class="kosong">Belum ada tahun ajaran.</div>
        @else
            <table>
                <thead><tr><th>Nama</th><th>Periode</th><th>Kelas</th><th>Pemilihan</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($tahunAjaran as $t)
                    <tr>
                        <td><strong>{{ $t->nama }}</strong></td>
                        <td class="kecil">{{ \Illuminate\Support\Carbon::parse($t->tanggal_mulai)->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($t->tanggal_selesai)->format('d M Y') }}</td>
                        <td>{{ $t->jumlah_kelas }}</td>
                        <td>{{ $t->jumlah_pemilihan }}</td>
                        <td>@if ($t->is_aktif)<span class="badge aktif">aktif</span>@endif</td>
                        <td>
                            <div class="aksi-baris">
                                @unless ($t->is_aktif)
                                    <form method="POST" action="{{ route('admin.tahun-ajaran.aktifkan', $t->id_tahun_ajaran) }}"
                                          data-konfirmasi="Jadikan {{ $t->nama }} tahun ajaran aktif? Halaman guru dan siswa akan mengikuti tahun ajaran ini.">
                                        @csrf
                                        <button class="btn kecil" type="submit">Aktifkan</button>
                                    </form>
                                @endunless
                                <a class="btn garis kecil" href="{{ route('admin.tahun-ajaran.edit', $t->id_tahun_ajaran) }}">Ubah</a>
                                @if (! $t->is_aktif && ! $t->jumlah_kelas && ! $t->jumlah_pemilihan)
                                    <form method="POST" action="{{ route('admin.tahun-ajaran.destroy', $t->id_tahun_ajaran) }}" data-konfirmasi="Hapus {{ $t->nama }}?">
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

    <div class="kartu">
        <h2>{{ $baru ? 'Tambah tahun ajaran' : 'Ubah '.$edit->nama }}</h2>
        <form method="POST" action="{{ $baru ? route('admin.tahun-ajaran.store') : route('admin.tahun-ajaran.update', $edit->id_tahun_ajaran) }}">
            @csrf
            @unless ($baru) @method('PUT') @endunless
            <div class="bidang">
                <label for="nama">Nama</label>
                <input id="nama" name="nama" value="{{ old('nama', $edit->nama) }}" placeholder="2026/2027" required pattern="\d{4}/\d{4}" maxlength="9">
            </div>
            <div class="bidang">
                <label for="tanggal_mulai">Tanggal mulai</label>
                <input id="tanggal_mulai" type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', $edit->tanggal_mulai?->format('Y-m-d')) }}" required>
            </div>
            <div class="bidang">
                <label for="tanggal_selesai">Tanggal selesai</label>
                <input id="tanggal_selesai" type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', $edit->tanggal_selesai?->format('Y-m-d')) }}" required>
            </div>
            <div class="aksi-baris">
                <button class="btn" type="submit">Simpan</button>
                @unless ($baru)<a class="btn garis" href="{{ route('admin.tahun-ajaran.index') }}">Batal</a>@endunless
            </div>
        </form>
    </div>
</div>
@endsection
