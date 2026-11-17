@extends('layouts.admin')
@php $baru = ! $kelas->exists; @endphp
@section('judul', $baru ? 'Kelas Baru' : 'Ubah Kelas')
@section('jalur', 'Akademik / Kelas / '.($baru ? 'Baru' : $kelas->nama_kelas))
@section('isi')
<h1>{{ $baru ? 'Kelas Baru' : 'Ubah '.$kelas->nama_kelas }}</h1>
<p class="subjudul">Seorang guru hanya bisa menjadi wali satu kelas per tahun ajaran.</p>

<div class="kartu" style="max-width:640px">
    <form method="POST" action="{{ $baru ? route('admin.kelas.store') : route('admin.kelas.update', $kelas->id_kelas) }}">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        <div class="form-grid">
            <div class="bidang">
                <label for="id_tahun_ajaran">Tahun ajaran</label>
                <select id="id_tahun_ajaran" name="id_tahun_ajaran" required>
                    @foreach ($tahunAjaran as $t)
                        <option value="{{ $t->id_tahun_ajaran }}" @selected((int) old('id_tahun_ajaran', $kelas->id_tahun_ajaran) === $t->id_tahun_ajaran)>
                            {{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="bidang">
                <label for="nama_kelas">Nama kelas</label>
                <input id="nama_kelas" name="nama_kelas" value="{{ old('nama_kelas', $kelas->nama_kelas) }}" placeholder="X IPA 1" required maxlength="20">
            </div>
            <div class="bidang">
                <label for="tingkat">Tingkat</label>
                <select id="tingkat" name="tingkat" required>
                    @foreach ([10, 11, 12] as $t)
                        <option value="{{ $t }}" @selected((int) old('tingkat', $kelas->tingkat) === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bidang">
                <label for="jurusan">Jurusan</label>
                <input id="jurusan" name="jurusan" value="{{ old('jurusan', $kelas->jurusan) }}" placeholder="IPA / IPS / Fase E" maxlength="30">
            </div>
            <div class="bidang">
                <label for="ruangan">Ruangan</label>
                <input id="ruangan" name="ruangan" value="{{ old('ruangan', $kelas->ruangan) }}" placeholder="R-101" maxlength="20">
            </div>
            <div class="bidang">
                <label for="id_wali_kelas">Wali kelas</label>
                <select id="id_wali_kelas" name="id_wali_kelas">
                    <option value="">— belum ditentukan —</option>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id_guru }}" @selected((int) old('id_wali_kelas', $kelas->id_wali_kelas) === $g->id_guru)>{{ $g->nama_guru }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="aksi-baris">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn garis" href="{{ $baru ? route('admin.kelas.index') : route('admin.kelas.show', $kelas->id_kelas) }}">Batal</a>
        </div>
    </form>

    @unless ($baru)
        <form method="POST" action="{{ route('admin.kelas.destroy', $kelas->id_kelas) }}" style="margin-top:18px"
              data-konfirmasi="Hapus kelas {{ $kelas->nama_kelas }}? Hanya kelas kosong yang dapat dihapus.">
            @csrf @method('DELETE')
            <button class="btn merah kecil" type="submit">Hapus kelas</button>
        </form>
    @endunless
</div>
@endsection
