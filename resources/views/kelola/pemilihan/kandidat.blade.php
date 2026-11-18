@extends('layouts.pemilihan')
@section('judul', 'Kandidat')
@section('isi')
@php $id = $pemilihan->id_pemilihan; $draf = $pemilihan->status === 'draf'; @endphp

<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <h1>Kandidat</h1>
        <p class="subjudul">{{ $pemilihan->judul }} — <span class="badge {{ $pemilihan->status }}">{{ $pemilihan->status }}</span></p>
    </div>
</div>

@if (! $draf)
    <div class="err">
        Pemilihan sudah dibuka. Daftar kandidat terkunci dan tidak dapat diubah lagi, agar hasil pemungutan tetap sah.
        Untuk memperbaiki data, kembalikan pemilihan ke draf dari halaman Monitor (hanya bisa bila belum ada suara).
    </div>
@endif

<div class="grid grid-3" style="margin-bottom:16px">
    @forelse ($kandidat as $k)
        <div class="kandidat-kartu">
            <div class="kandidat-foto">
                @if ($k->foto_url)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($k->foto_url) }}" alt="Foto kandidat {{ $k->nomor_urut }}">
                @else
                    {{ strtoupper(substr($k->nama_ketua ?? 'K', 0, 1)) }}
                @endif
            </div>
            <div class="kandidat-isi">
                <span class="nomor">No. {{ str_pad((string) $k->nomor_urut, 2, '0', STR_PAD_LEFT) }}</span>
                <div class="nama-pasangan">
                    {{ $k->nama_ketua }}@if ($k->nama_wakil) &amp; {{ $k->nama_wakil }}@endif
                </div>
                <div class="visi">{{ $k->visi ?: 'Belum ada visi.' }}</div>
                @if ($draf)
                    <form method="POST" action="{{ route('kelola.pemilihan.kandidat.hapus', [$id, $k->id_kandidat]) }}"
                          onsubmit="return confirm('Hapus kandidat ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn merah" type="submit">Hapus</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="kosong" style="grid-column:1/-1">Belum ada kandidat.</div>
    @endforelse
</div>

@if ($draf)
    <div class="kartu">
        <h2>Tambah Kandidat</h2>
        <form method="POST" action="{{ route('kelola.pemilihan.kandidat.simpan', $id) }}" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-2" style="margin-bottom:14px">
                <div>
                    <label for="nomor_urut">Nomor urut</label>
                    <input id="nomor_urut" type="number" name="nomor_urut" min="1" max="99" required
                           value="{{ old('nomor_urut', $nomorBerikutnya) }}">
                </div>
                <div>
                    <label for="foto">Foto (opsional, maks 2 MB)</label>
                    <input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>

            <div class="grid grid-2" style="margin-bottom:14px">
                <div>
                    <label for="id_siswa_ketua">Siswa ketua</label>
                    <select id="id_siswa_ketua" name="id_siswa_ketua" required>
                        <option value="">— pilih —</option>
                        @foreach ($siswa as $s)
                            <option value="{{ $s->id_siswa }}" @selected((int) old('id_siswa_ketua') === (int) $s->id_siswa)>
                                {{ $s->nama_siswa }} ({{ $s->nis }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="id_siswa_wakil">Siswa wakil (opsional)</label>
                    <select id="id_siswa_wakil" name="id_siswa_wakil">
                        <option value="">— tanpa wakil —</option>
                        @foreach ($siswa as $s)
                            <option value="{{ $s->id_siswa }}" @selected((int) old('id_siswa_wakil') === (int) $s->id_siswa)>
                                {{ $s->nama_siswa }} ({{ $s->nis }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom:14px">
                <label for="visi">Visi</label>
                <textarea id="visi" name="visi" rows="2">{{ old('visi') }}</textarea>
            </div>
            <div style="margin-bottom:18px">
                <label for="misi">Misi</label>
                <textarea id="misi" name="misi" rows="3">{{ old('misi') }}</textarea>
            </div>

            <button class="btn" type="submit">Tambah Kandidat</button>
        </form>
    </div>
@endif
@endsection
