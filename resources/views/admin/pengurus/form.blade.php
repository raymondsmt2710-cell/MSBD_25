@extends('layouts.admin')
@section('judul', $judul)
@section('jalur', 'Organisasi / '.$judul)
@section('isi')
<div class="kepala">
    <div>
        <h1>{{ $judul }}</h1>
        <p class="subjudul">{{ $subjudul }}</p>
    </div>
</div>

@if (! empty($tahunAjaran))
    <div class="kartu">@include('admin._pilih_tahun')</div>
@endif

<div class="grid" style="grid-template-columns:minmax(0,3fr) minmax(260px,2fr);align-items:start">
    <div class="kartu">
        <h2>Susunan pengurus</h2>
        @if ($jabatan->isEmpty())
            <div class="kosong">Belum ada jabatan untuk lingkup ini. Tambahkan di Master Jabatan.</div>
        @else
            <table>
                <thead><tr><th>Jabatan</th><th>Pemegang</th></tr></thead>
                <tbody>
                @foreach ($jabatan as $j)
                    @php $pemegang = $pengurus[$j->id_jabatan] ?? collect(); @endphp
                    <tr>
                        <td style="width:35%"><strong>{{ $j->nama_jabatan }}</strong><br><span class="kecil">{{ $pemegang->count() }}/{{ $j->kuota }}</span></td>
                        <td>
                            @forelse ($pemegang as $p)
                                <div class="aksi-baris" style="margin:3px 0">
                                    <span>{{ $p->nama_siswa }} <span class="kecil">{{ $p->nis }}{{ ! empty($p->bidang) ? ' · '.$p->bidang : '' }}</span></span>
                                    <form method="POST" action="{{ $ruteHapus($p->id) }}" data-konfirmasi="Lepas {{ $p->nama_siswa }} dari jabatan {{ $j->nama_jabatan }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn merah kecil" type="submit">Lepas</button>
                                    </form>
                                </div>
                            @empty
                                <span class="kecil">—</span>
                            @endforelse
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="kartu">
        <h2>Tetapkan pengurus</h2>
        <form method="POST" action="{{ $aksiSimpan }}">
            @csrf
            <div class="bidang">
                <label for="id_jabatan">Jabatan</label>
                <select id="id_jabatan" name="id_jabatan" required>
                    <option value="">— pilih —</option>
                    @foreach ($jabatan as $j)
                        @php $penuh = ($pengurus[$j->id_jabatan] ?? collect())->count() >= $j->kuota; @endphp
                        <option value="{{ $j->id_jabatan }}" @disabled($penuh) @selected((int) old('id_jabatan') === $j->id_jabatan)>
                            {{ $j->nama_jabatan }}{{ $penuh ? ' (penuh)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="bidang">
                <label for="id_siswa">Siswa</label>
                <select id="id_siswa" name="id_siswa" required>
                    <option value="">— pilih —</option>
                    @foreach ($calon as $c)
                        <option value="{{ $c->id_siswa }}" @selected((int) old('id_siswa') === $c->id_siswa)>{{ $c->nama_siswa }} ({{ $c->nis }})</option>
                    @endforeach
                </select>
                <span class="kecil">Siswa yang sudah memegang jabatan di sini tidak ditampilkan.</span>
            </div>
            @if ($pakaiBidang)
                <div class="bidang">
                    <label for="bidang">Bidang / seksi (opsional)</label>
                    <input id="bidang" name="bidang" value="{{ old('bidang') }}" maxlength="50" placeholder="Seksi Keagamaan">
                </div>
            @endif
            <button class="btn" type="submit">Tetapkan</button>
        </form>
    </div>
</div>
@endsection
