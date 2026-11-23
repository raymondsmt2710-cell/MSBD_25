@extends('layouts.pemilihan')
@section('judul', 'Pengurus '.$organisasi->nama_organisasi)
@section('isi')
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <h1>{{ $organisasi->nama_organisasi }}</h1>
        <p class="subjudul">
            Pembina: <strong>{{ $organisasi->pembina ?? '—' }}</strong>
        </p>
    </div>
    <a class="btn garis" href="{{ route('perangkat.index') }}">← Kembali</a>
</div>

@if ($organisasi->deskripsi)
    <div class="kartu">
        <p class="kecil" style="margin:0;line-height:1.7">{{ $organisasi->deskripsi }}</p>
    </div>
@endif

<div class="kartu">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <h2 style="margin:0">Susunan Pengurus</h2>

        <form method="GET" action="{{ route('perangkat.organisasi', $organisasi->id_organisasi) }}"
              style="display:flex;gap:8px;align-items:center">
            <label for="tahun" class="kecil" style="margin:0;white-space:nowrap">Tahun ajaran</label>
            <select id="tahun" name="tahun" onchange="this.form.submit()" style="width:auto">
                @foreach ($daftarTahun as $t)
                    <option value="{{ $t->id_tahun_ajaran }}" @selected((int) $t->id_tahun_ajaran === $idTahun)>
                        {{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if ($jabatan->isEmpty())
        <div class="kosong">Belum ada jabatan untuk organisasi. Jalankan seeder jabatan terlebih dahulu.</div>
    @else
        <table>
            <thead>
            <tr><th style="width:60px">No</th><th>Jabatan</th><th>Nama</th><th>NIS</th><th>Bidang</th></tr>
            </thead>
            <tbody>
            @foreach ($jabatan as $i => $j)
                @php $orang = $pengurus[$j->id_jabatan] ?? collect(); @endphp
                @if ($orang->isEmpty())
                    <tr>
                        <td class="kecil">{{ $i + 1 }}</td>
                        <td><strong>{{ $j->nama_jabatan }}</strong></td>
                        <td class="kecil" colspan="3"><em>belum diisi</em></td>
                    </tr>
                @else
                    @foreach ($orang as $p)
                        <tr>
                            <td class="kecil">{{ $i + 1 }}</td>
                            <td><strong>{{ $j->nama_jabatan }}</strong></td>
                            <td>{{ $p->nama_siswa }}</td>
                            <td class="kecil">{{ $p->nis }}</td>
                            <td class="kecil">{{ $p->bidang ?: '—' }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
            </tbody>
        </table>

        @if ($pengurus->isEmpty())
            <p class="kecil" style="margin:14px 0 0">
                Susunan pengurus belum pernah diisi untuk tahun ajaran ini.
            </p>
        @endif
    @endif
</div>
@endsection
