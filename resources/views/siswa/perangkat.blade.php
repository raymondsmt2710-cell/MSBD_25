@extends('layouts.siswa')

@section('judul', 'Perangkat Kelas & Organisasi')

@section('isi')

<div class="hero">
    <div class="hero-inner">
        <div class="lencana">
            <span class="dot"></span>
            Perangkat
        </div>
        <h1>Perangkat Kelas &amp; Organisasi</h1>
        <p>
            {{ $siswa->nama_siswa }}
            <span class="pemisah">·</span> NIS {{ $siswa->nis }}
        </p>
    </div>
</div>

<section>
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Struktur</div>
            <h2>Perangkat Kelas</h2>
            <p>Jabatan pengurus pada kelas tempat Anda terdaftar.</p>
        </div>

        <div class="kartu">
            @if ($kelas->isEmpty())
                <p class="kosong">
                    Belum ada data perangkat kelas untuk kelas Anda.
                </p>
            @else
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>Jabatan</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kelas as $k)
                            <tr>
                                <td>{{ $k->nama_jabatan }}</td>
                                <td>{{ $k->nama_siswa }}</td>
                                <td>{{ $k->nama_kelas }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</section>

<section>
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Struktur</div>
            <h2>Perangkat Organisasi</h2>
            <p>Jabatan Anda pada organisasi sekolah.</p>
        </div>

        <div class="kartu">
            @if ($organisasi->isEmpty())
                <p class="kosong">
                    Belum ada data perangkat organisasi untuk Anda.
                </p>
            @else
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>Organisasi</th>
                            <th>Jabatan</th>
                            <th>Bidang</th>
                            <th>Tahun Ajaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($organisasi as $o)
                            <tr>
                                <td>{{ $o->nama_organisasi }}</td>
                                <td>{{ $o->nama_jabatan }}</td>
                                <td>{{ $o->bidang ?? '—' }}</td>
                                <td>{{ $o->nama_tahun_ajaran }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</section>

@endsection
