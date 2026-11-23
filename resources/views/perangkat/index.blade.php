@extends('layouts.pemilihan')
@section('judul', 'Perangkat Organisasi & Kelas')
@section('isi')
<h1>Perangkat Organisasi &amp; Kelas</h1>
<p class="subjudul">Pilih kelas atau organisasi untuk melihat susunan pengurusnya.</p>

<div class="kartu">
    <h2>Kelas</h2>

    @if ($kelas->isEmpty())
        <div class="kosong">Belum ada kelas.</div>
    @else
        <div class="grid grid-3" style="margin-top:12px">
            @foreach ($kelas as $k)
                <a href="{{ route('perangkat.kelas', $k->id_kelas) }}" class="kandidat-kartu" style="text-decoration:none">
                    <div class="kandidat-isi">
                        <span class="nomor">{{ $k->tingkat }}{{ $k->jurusan ? ' '.$k->jurusan : '' }}</span>
                        <div class="nama-pasangan">{{ $k->nama_kelas }}</div>
                        <div class="kecil" style="line-height:1.6">
                            <div>Wali kelas: {{ $k->wali_kelas ?? '—' }}</div>
                            <div>Ruang: {{ $k->ruangan ?? '—' }}</div>
                            <div>Tahun: {{ $k->tahun_ajaran ?? '—' }}</div>
                        </div>
                        <div class="meta" style="margin-top:auto">
                            <span class="pill">{{ $k->jumlah_pengurus }} pengurus</span>
                            <span class="pill">{{ $k->jumlah_anggota }} siswa</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

<div class="kartu">
    <h2>Organisasi</h2>

    @if ($organisasi->isEmpty())
        <div class="kosong">Belum ada organisasi.</div>
    @else
        <div class="grid grid-3" style="margin-top:12px">
            @foreach ($organisasi as $o)
                <a href="{{ route('perangkat.organisasi', $o->id_organisasi) }}" class="kandidat-kartu" style="text-decoration:none">
                    <div class="kandidat-isi">
                        <span class="nomor">{{ $o->singkatan ?: 'ORG' }}</span>
                        <div class="nama-pasangan">{{ $o->nama_organisasi }}</div>
                        <div class="kecil" style="line-height:1.6">
                            <div>Pembina: {{ $o->pembina ?? '—' }}</div>
                            <div class="visi">{{ $o->deskripsi ?: 'Belum ada deskripsi.' }}</div>
                        </div>
                        <div class="meta" style="margin-top:auto">
                            <span class="pill">{{ $o->jumlah_pengurus }} pengurus</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
