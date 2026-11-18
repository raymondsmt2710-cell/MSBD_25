@extends('layouts.pemilihan')
@section('judul', 'Atur Pengurus '.$kelas->nama_kelas)
@section('isi')
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <h1>Atur Pengurus — {{ $kelas->nama_kelas }}</h1>
        <p class="subjudul">Wali kelas: <strong>{{ $kelas->wali_kelas ?? '—' }}</strong></p>
    </div>
    <a class="btn garis" href="{{ route('perangkat.kelola.index') }}">← Kembali</a>
</div>

@if ($siswa->isEmpty())
    <div class="err">Kelas ini belum punya anggota. Tambahkan siswa ke kelas terlebih dahulu.</div>
@endif

<div class="kartu">
    <h2>Susunan Pengurus</h2>
    <p class="kecil" style="margin:-6px 0 16px">
        Kosongkan pilihan untuk mengosongkan jabatan. Satu siswa hanya boleh memegang satu jabatan.
    </p>

    <form method="POST" action="{{ route('perangkat.kelola.kelas.simpan', $kelas->id_kelas) }}">
        @csrf

        @foreach ($jabatan as $j)
            <div style="margin-bottom:14px">
                <label for="jabatan-{{ $j->id_jabatan }}">
                    {{ $j->nama_jabatan }}
                    @if ($j->kuota > 1)<span class="kecil">(kuota {{ $j->kuota }})</span>@endif
                </label>
                <select id="jabatan-{{ $j->id_jabatan }}" name="pengurus[{{ $j->id_jabatan }}]">
                    <option value="">— belum diisi —</option>
                    @foreach ($siswa as $s)
                        <option value="{{ $s->id_siswa }}"
                            @selected((int) old('pengurus.'.$j->id_jabatan, $terpilih[$j->id_jabatan] ?? 0) === (int) $s->id_siswa)>
                            {{ $s->nama_siswa }} ({{ $s->nis }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endforeach

        <div class="aksi-baris" style="margin-top:18px">
            <button class="btn" type="submit" @disabled($siswa->isEmpty())>Simpan Pengurus</button>
            <a class="btn garis" href="{{ route('perangkat.kelas', $kelas->id_kelas) }}">Lihat Hasil</a>
        </div>
    </form>
</div>
@endsection
