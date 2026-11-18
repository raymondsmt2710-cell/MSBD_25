@extends('layouts.pemilihan')
@section('judul', 'Atur Pengurus '.$organisasi->nama_organisasi)
@section('isi')
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <h1>Atur Pengurus — {{ $organisasi->nama_organisasi }}</h1>
        <p class="subjudul">Pembina: <strong>{{ $organisasi->pembina ?? '—' }}</strong></p>
    </div>
    <a class="btn garis" href="{{ route('perangkat.kelola.index') }}">← Kembali</a>
</div>

<div class="kartu">
    <h2>Pilih Tahun Ajaran</h2>
    <p class="kecil" style="margin:-6px 0 14px">
        Pengurus disimpan per tahun ajaran, sehingga susunan tahun lalu tetap tersimpan sebagai riwayat.
    </p>

    <form method="GET" action="{{ route('perangkat.kelola.organisasi', $organisasi->id_organisasi) }}"
          style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:220px">
            <label for="tahun-pilih">Tahun ajaran</label>
            <select id="tahun-pilih" name="tahun" onchange="this.form.submit()">
                @foreach ($daftarTahun as $t)
                    <option value="{{ $t->id_tahun_ajaran }}" @selected((int) $t->id_tahun_ajaran === $idTahun)>
                        {{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <button class="btn garis" type="submit">Tampilkan</button>
    </form>
</div>

<div class="kartu">
    <h2>Susunan Pengurus</h2>
    <p class="kecil" style="margin:-6px 0 16px">
        Kosongkan pilihan untuk mengosongkan jabatan. Kolom "Bidang" hanya perlu diisi untuk
        jabatan seperti Koordinator Bidang.
    </p>

    <form method="POST" action="{{ route('perangkat.kelola.organisasi.simpan', $organisasi->id_organisasi) }}">
        @csrf
        <input type="hidden" name="id_tahun_ajaran" value="{{ $idTahun }}">

        @foreach ($jabatan as $j)
            <div style="margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #f1f2f4">
                <label for="jabatan-{{ $j->id_jabatan }}">
                    {{ $j->nama_jabatan }}
                    @if ($j->kuota > 1)<span class="kecil">(kuota {{ $j->kuota }})</span>@endif
                </label>

                <div class="grid grid-2" style="gap:10px">
                    <select id="jabatan-{{ $j->id_jabatan }}" name="pengurus[{{ $j->id_jabatan }}]">
                        <option value="">— belum diisi —</option>
                        @foreach ($siswa as $s)
                            <option value="{{ $s->id_siswa }}"
                                @selected((int) old('pengurus.'.$j->id_jabatan, $terpilih[$j->id_jabatan] ?? 0) === (int) $s->id_siswa)>
                                {{ $s->nama_siswa }} ({{ $s->nis }})
                            </option>
                        @endforeach
                    </select>

                    <input type="text" name="bidang[{{ $j->id_jabatan }}]" maxlength="50"
                           placeholder="Bidang (opsional)"
                           value="{{ old('bidang.'.$j->id_jabatan, $bidang[$j->id_jabatan] ?? '') }}">
                </div>
            </div>
        @endforeach

        <div class="aksi-baris" style="margin-top:18px">
            <button class="btn" type="submit">Simpan Pengurus</button>
            <a class="btn garis" href="{{ route('perangkat.organisasi', $organisasi->id_organisasi) }}">Lihat Hasil</a>
        </div>
    </form>
</div>
@endsection
