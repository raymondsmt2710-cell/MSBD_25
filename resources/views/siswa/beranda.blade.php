@extends('layouts.siswa')

@section('judul', 'Beranda Siswa')

@section('isi')

<div class="hero">
    <div class="hero-inner">
        <div class="lencana">
            <span class="dot"></span>
            Beranda Siswa
        </div>
        <h1>Selamat datang, <span>{{ $siswa->nama_siswa }}</span></h1>
        <p>
            NIS {{ $siswa->nis }}
            <span class="pemisah">·</span> Kelas {{ $kelas }}
            <span class="pemisah">·</span> Status {{ $siswa->status }}
        </p>
    </div>
</div>

<div class="statistik">
    <div class="statistik-grid">
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="check2-square" ukuran="19" /></div>
            <div class="angka">{{ number_format($ringkasan['persen'], 2) }}%</div>
            <div class="label">Kehadiran</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="check-lg" ukuran="19" /></div>
            <div class="angka">{{ $ringkasan['hadir'] }}</div>
            <div class="label">Hadir</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="calendar-week" ukuran="19" /></div>
            <div class="angka">{{ $ringkasan['izin'] }}</div>
            <div class="label">Izin</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="person" ukuran="19" /></div>
            <div class="angka">{{ $ringkasan['sakit'] }}</div>
            <div class="label">Sakit</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="exclamation-triangle" ukuran="19" /></div>
            <div class="angka">{{ $ringkasan['alpa'] }}</div>
            <div class="label">Alpa</div>
        </div>
    </div>
</div>

<section>
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Hari Ini</div>
            <h2>Jadwal Pelajaran</h2>
            <p>{{ \Illuminate\Support\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</p>
        </div>

        <div class="kartu">
            @forelse ($jadwalHariIni as $j)
                <div class="aksi-baris">
                    <div>
                        <div class="aksi-jam">
                            {{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}
                        </div>
                        <div class="aksi-judul">{{ $j->nama_mapel }} &middot; {{ $j->nama_guru }}</div>
                    </div>
                    <span class="pill">{{ $j->nama_kelas }}</span>
                </div>
            @empty
                <p class="kosong">Tidak ada jadwal pelajaran untuk hari ini.</p>
            @endforelse
        </div>
    </div>
</section>

<section>
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Riwayat</div>
            <h2>Presensi Terakhir</h2>
            <p>Lima catatan kehadiran terbaru Anda.</p>
        </div>

        <div class="kartu">
            @if ($riwayatTerakhir->isEmpty())
                <p class="kosong">Belum ada catatan presensi.</p>
            @else
                <table class="tabel tabel-bertumpuk">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Mata Pelajaran</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($riwayatTerakhir as $r)
                            <tr>
                                <td data-label="Tanggal">
                                    {{ \Illuminate\Support\Carbon::parse($r->tanggal)->locale('id')->isoFormat('D MMM YYYY') }}
                                    <div class="kecil">{{ substr($r->jam_mulai, 0, 5) }}–{{ substr($r->jam_selesai, 0, 5) }}</div>
                                </td>
                                <td data-label="Mata Pelajaran">{{ $r->nama_mapel }}</td>
                                <td data-label="Status"><span class="badge {{ $r->status }}">{{ $r->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="form-aksi" style="margin-top:14px">
                    <a class="btn garis" href="{{ route('siswa.presensi.riwayat') }}">Lihat semua presensi</a>
                </div>
            @endif
        </div>
    </div>
</section>

@if ($pemilihanAktif)
    <section>
        <div class="wrap">
            <div class="judul-bagian">
                <div class="kicker">Pemilihan</div>
                <h2>{{ $pemilihanAktif->judul }}</h2>
                <p>Suara Anda rahasia dan tidak dapat diubah setelah dikirim.</p>
            </div>

            <div class="kartu">
                <div class="aksi-baris">
                    <div>
                        <div class="aksi-jam">{{ ucfirst($pemilihanAktif->status) }}</div>
                        <div class="aksi-judul">
                            Berakhir
                            {{ \Illuminate\Support\Carbon::parse($pemilihanAktif->waktu_selesai)->locale('id')->isoFormat('D MMM YYYY, HH:mm') }}
                        </div>
                    </div>

                    <div class="aksi-tombol">
                        @if ($pemilihanAktif->sudah_memilih)
                            <span class="status-pil">Suara sudah tercatat</span>
                        @elseif ($pemilihanAktif->status === 'draf')
                            <span class="status-pil">Menunggu dibuka panitia</span>
                        @else
                            <a class="btn" href="{{ route('siswa.pemilihan.index') }}">Pilih Sekarang</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

@endsection
