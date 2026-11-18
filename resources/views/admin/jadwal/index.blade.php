@extends('layouts.admin')
@section('judul', 'Jadwal Pelajaran')
@section('jalur', 'Akademik / Jadwal Pelajaran')
@section('isi')
@php $baru = ! $edit->exists; @endphp
<h1>Jadwal Pelajaran{{ $kelas ? ' — '.$kelas->nama_kelas : '' }}</h1>
<p class="subjudul">Jadwal menjadi dasar guru membuka sesi presensi. Bentrok kelas dan bentrok guru ditolak otomatis.</p>

<div class="kartu">
    <form method="GET" class="filter">
        <select name="tahun" onchange="this.form.kelas.value='';this.form.submit()">
            @foreach ($tahunAjaran as $t)
                <option value="{{ $t->id_tahun_ajaran }}" @selected($idTahun === $t->id_tahun_ajaran)>{{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}</option>
            @endforeach
        </select>
        <select name="kelas" onchange="this.form.submit()">
            <option value="">— pilih kelas —</option>
            @foreach ($daftarKelas as $k)
                <option value="{{ $k->id_kelas }}" @selected($kelas?->id_kelas === $k->id_kelas)>{{ $k->nama_kelas }} ({{ $k->jumlah_jadwal }})</option>
            @endforeach
        </select>
        <select name="semester" onchange="this.form.submit()">
            @foreach (['ganjil', 'genap'] as $s)
                <option value="{{ $s }}" @selected($semester === $s)>Semester {{ $s }}</option>
            @endforeach
        </select>
        <noscript><button class="btn garis" type="submit">Tampilkan</button></noscript>
    </form>

    @if (! $kelas)
        <div class="kosong">Pilih kelas untuk melihat dan menyusun jadwalnya.</div>
    @endif
</div>

@if ($kelas)
<div class="grid" style="grid-template-columns:minmax(0,2fr) minmax(280px,1fr);align-items:start">
    <div>
        @foreach ($hariList as $h)
            <div class="kartu">
                <h2 style="text-transform:capitalize">{{ $h }}</h2>
                @if (empty($jadwal[$h]))
                    <span class="kecil">Tidak ada pelajaran.</span>
                @else
                    <table>
                        <tbody>
                        @foreach ($jadwal[$h] as $j)
                            <tr style="{{ $edit->id_jadwal === $j->id_jadwal ? 'background:#f0fdfa' : '' }}">
                                <td style="width:110px"><strong>{{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}</strong></td>
                                <td><span class="badge">{{ $j->kode_mapel }}</span> {{ $j->nama_mapel }}</td>
                                <td>{{ $j->nama_guru }}</td>
                                <td class="kecil">{{ $j->jumlah_sesi }} sesi</td>
                                <td>
                                    <div class="aksi-baris">
                                        <a class="btn garis kecil" href="{{ route('admin.jadwal.edit', $j->id_jadwal) }}">Ubah</a>
                                        @unless ($j->jumlah_sesi)
                                            <form method="POST" action="{{ route('admin.jadwal.destroy', $j->id_jadwal) }}" data-konfirmasi="Hapus jadwal {{ $j->nama_mapel }} hari {{ $h }}?">
                                                @csrf @method('DELETE')
                                                <button class="btn merah kecil" type="submit">Hapus</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach
    </div>

    <div class="kartu" style="position:sticky;top:70px">
        <h2>{{ $baru ? 'Tambah jam pelajaran' : 'Ubah jadwal' }}</h2>
        <form method="POST" action="{{ $baru ? route('admin.jadwal.store') : route('admin.jadwal.update', $edit->id_jadwal) }}">
            @csrf
            @unless ($baru) @method('PUT') @endunless
            <input type="hidden" name="id_kelas" value="{{ $kelas->id_kelas }}">
            <input type="hidden" name="semester" value="{{ $semester }}">

            <div class="bidang">
                <label for="hari">Hari</label>
                <select id="hari" name="hari" required>
                    @foreach ($hariList as $h)
                        <option value="{{ $h }}" @selected(old('hari', $edit->hari) === $h)>{{ ucfirst($h) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-grid">
                <div class="bidang">
                    <label for="jam_mulai">Mulai</label>
                    <input id="jam_mulai" type="time" name="jam_mulai" value="{{ old('jam_mulai', substr((string) $edit->jam_mulai, 0, 5)) }}" required>
                </div>
                <div class="bidang">
                    <label for="jam_selesai">Selesai</label>
                    <input id="jam_selesai" type="time" name="jam_selesai" value="{{ old('jam_selesai', substr((string) $edit->jam_selesai, 0, 5)) }}" required>
                </div>
            </div>
            <div class="bidang">
                <label for="id_mapel">Mata pelajaran</label>
                <select id="id_mapel" name="id_mapel" required>
                    <option value="">— pilih —</option>
                    @foreach ($mapel as $m)
                        <option value="{{ $m->id_mapel }}" @selected((int) old('id_mapel', $edit->id_mapel) === $m->id_mapel)>{{ $m->kode_mapel }} — {{ $m->nama_mapel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bidang">
                <label for="id_guru">Guru pengajar</label>
                <select id="id_guru" name="id_guru" required>
                    <option value="">— pilih —</option>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id_guru }}" @selected((int) old('id_guru', $edit->id_guru) === $g->id_guru)>{{ $g->nama_guru }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aksi-baris">
                <button class="btn" type="submit">Simpan</button>
                @unless ($baru)
                    <a class="btn garis" href="{{ route('admin.jadwal.index', ['kelas' => $kelas->id_kelas, 'semester' => $semester]) }}">Batal</a>
                @endunless
            </div>
        </form>
    </div>
</div>
@endif
@endsection
