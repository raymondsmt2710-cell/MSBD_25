@extends('layouts.pemilihan')
@section('judul', $pemilihan->exists ? 'Ubah Pemilihan' : 'Buat Pemilihan')
@section('isi')
<h1>{{ $pemilihan->exists ? 'Ubah Pemilihan' : 'Buat Pemilihan' }}</h1>
<p class="subjudul">Pemilihan baru selalu dibuat dengan status <strong>draf</strong>. Data masih bisa diubah sampai pemilihan dibuka.</p>

<div class="kartu">
    <form method="POST"
          action="{{ $pemilihan->exists ? route('kelola.pemilihan.ubah.simpan', $pemilihan->id_pemilihan) : route('kelola.pemilihan.simpan') }}">
        @csrf

        <div style="margin-bottom:14px">
            <label for="judul">Judul pemilihan</label>
            <input id="judul" name="judul" value="{{ old('judul', $pemilihan->judul) }}" maxlength="100" required>
        </div>

        <div style="margin-bottom:14px">
            <label for="deskripsi">Deskripsi (opsional)</label>
            <textarea id="deskripsi" name="deskripsi" rows="3">{{ old('deskripsi', $pemilihan->deskripsi) }}</textarea>
        </div>

        <div class="grid grid-2" style="margin-bottom:14px">
            <div>
                <label for="id_organisasi">Organisasi</label>
                <select id="id_organisasi" name="id_organisasi" required>
                    <option value="">— pilih —</option>
                    @foreach ($organisasi as $o)
                        <option value="{{ $o->id_organisasi }}"
                            @selected((int) old('id_organisasi', $pemilihan->id_organisasi) === (int) $o->id_organisasi)>
                            {{ $o->nama_organisasi }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="id_tahun_ajaran">Tahun ajaran</label>
                <select id="id_tahun_ajaran" name="id_tahun_ajaran" required>
                    <option value="">— pilih —</option>
                    @foreach ($tahunAjaran as $t)
                        <option value="{{ $t->id_tahun_ajaran }}"
                            @selected((int) old('id_tahun_ajaran', $pemilihan->id_tahun_ajaran) === (int) $t->id_tahun_ajaran)>
                            {{ $t->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-2" style="margin-bottom:18px">
            <div>
                <label for="waktu_mulai">Waktu mulai</label>
                <input id="waktu_mulai" type="datetime-local" name="waktu_mulai" required
                       value="{{ old('waktu_mulai', $pemilihan->waktu_mulai?->format('Y-m-d\TH:i')) }}">
            </div>
            <div>
                <label for="waktu_selesai">Waktu selesai</label>
                <input id="waktu_selesai" type="datetime-local" name="waktu_selesai" required
                       value="{{ old('waktu_selesai', $pemilihan->waktu_selesai?->format('Y-m-d\TH:i')) }}">
            </div>
        </div>

        <div class="aksi-baris">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn garis" href="{{ route('kelola.pemilihan.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection
