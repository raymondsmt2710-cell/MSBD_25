@extends('layouts.guru')

@section('judul', 'Beranda Guru')

@section('isi')

{{-- ---------- Hero ---------- --}}
<div class="hero">
    <div class="hero-inner">
        <div class="lencana">
            <span class="dot"></span>
            Beranda Guru
        </div>
        <h1>Selamat datang, <span>{{ $guru->nama_guru }}</span></h1>
        <p>
            NIP {{ $guru->nip }}
            @if ($kelasDiampu->isNotEmpty())
                <span class="pemisah">·</span>
                {{ $kelasDiampu->pluck('nama_mapel')->unique()->implode(', ') }}
                <span class="pemisah">·</span>
                Kelas {{ $kelasDiampu->pluck('nama_kelas')->unique()->implode(', ') }}
            @endif
        </p>
    </div>
</div>

{{-- ---------- Statistik ---------- --}}
<div class="statistik">
    <div class="statistik-grid">
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="calendar-week" ukuran="19" /></div>
            <div class="angka">{{ $ringkasan['jadwal_hari_ini'] }}</div>
            <div class="label">Jadwal Hari Ini</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="qr-code-scan" ukuran="19" /></div>
            <div class="angka">{{ $ringkasan['sesi_terbuka'] }}</div>
            <div class="label">Sesi Terbuka</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="grid-3x3" ukuran="19" /></div>
            <div class="angka">{{ $ringkasan['sesi_pekan_ini'] }}</div>
            <div class="label">Sesi Pekan Ini</div>
        </div>
        <div class="stat-kartu">
            <div class="ikon"><x-ikon nama="check-lg" ukuran="19" /></div>
            <div class="angka">{{ number_format($ringkasan['kehadiran_pekan_ini'], 2) }}%</div>
            <div class="label">Kehadiran Siswa</div>
        </div>
    </div>
</div>

{{-- ---------- Jadwal hari ini ---------- --}}
<section>
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Hari Ini</div>
            <h2>Jadwal Hari Ini</h2>
            <p>{{ \Illuminate\Support\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</p>
        </div>

        <div class="kartu">
            @if ($jadwalHariIni->isEmpty())
                <p class="kosong">
                    Tidak ada jadwal mengajar untuk hari ini.
                </p>
            @else
                @foreach ($jadwalHariIni as $j)
                    <div class="aksi-baris">
                        <div>
                            <div class="aksi-jam">
                                {{ \Illuminate\Support\Carbon::parse($j->jam_mulai)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($j->jam_selesai)->format('H:i') }}
                            </div>
                            <div class="aksi-judul">{{ $j->nama_kelas }} &middot; {{ $j->nama_mapel }}</div>
                        </div>

                        <div class="aksi-tombol">
                            @if ($j->id_sesi === null)
                                <form method="POST" action="{{ route('guru.presensi.buka', $j->id_jadwal) }}">
                                    @csrf
                                    <button class="btn" type="submit">Buka sesi</button>
                                </form>
                            @else
                                <span class="status-pil">{{ $j->status_sesi }}</span>
                                <a class="btn garis" href="{{ route('guru.presensi.sesi', $j->id_sesi) }}">QR</a>
                                @if ($j->status_sesi === 'dibuka')
                                    <form method="POST" action="{{ route('guru.presensi.tutup', $j->id_sesi) }}">
                                        @csrf
                                        <button class="btn oranye" type="submit">Tutup</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>

{{-- ---------- Sesi yang masih terbuka (termasuk yang tertinggal) ---------- --}}
@if ($sesiTerbuka->isNotEmpty())
    <section>
        <div class="wrap">
            <div class="judul-bagian">
                <div class="kicker">Perlu Ditutup</div>
                <h2>Sesi Masih Terbuka</h2>
                <p>Presensi belum final sampai sesi ditutup. Tutup sesi yang sudah selesai.</p>
            </div>

            <div class="kartu">
                @foreach ($sesiTerbuka as $s)
                    <div class="aksi-baris">
                        <div>
                            <div class="aksi-jam">
                                {{ \Illuminate\Support\Carbon::parse($s->tanggal)->locale('id')->isoFormat('D MMM YYYY') }}
                                &middot;
                                {{ \Illuminate\Support\Carbon::parse($s->jam_mulai)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($s->jam_selesai)->format('H:i') }}
                            </div>
                            <div class="aksi-judul">{{ $s->nama_kelas }} &middot; {{ $s->nama_mapel }}</div>
                        </div>

                        <div class="aksi-tombol">
                            <a class="btn garis" href="{{ route('guru.presensi.sesi', $s->id_sesi) }}">QR</a>
                            <form method="POST" action="{{ route('guru.presensi.tutup', $s->id_sesi) }}">
                                @csrf
                                <button class="btn oranye" type="submit">Tutup</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ---------- Rekap sesi terakhir ---------- --}}
<section>
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Riwayat</div>
            <h2>Rekap Sesi Terakhir</h2>
            <p>Lima sesi terakhir yang sudah Anda tutup.</p>
        </div>

        <div class="kartu">
            @if ($rekapSesi->isEmpty())
                <p class="kosong">Belum ada sesi yang tercatat.</p>
            @else
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kelas</th>
                            <th>Mapel</th>
                            <th>Hadir</th>
                            <th>Izin</th>
                            <th>Sakit</th>
                            <th>Alpa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rekapSesi as $r)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($r->tanggal)->locale('id')->isoFormat('D MMM YYYY') }}</td>
                                <td>{{ $r->nama_kelas }}</td>
                                <td>{{ $r->nama_mapel }}</td>
                                <td>{{ (int) $r->hadir }}</td>
                                <td>{{ (int) $r->izin }}</td>
                                <td>{{ (int) $r->sakit }}</td>
                                <td>{{ (int) $r->alpa }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</section>

{{-- ---------- Rekap sesi terakhir ---------- --}}
{{-- ---------- Menu cepat ---------- --}}
<section>
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Menu Cepat</div>
            <h2>Yang bisa Anda lakukan</h2>
            <p>Pintu masuk ke fitur presensi untuk akun guru.</p>
        </div>

        <div class="ikon-grid">
            <a class="ikon-kartu" href="{{ route('guru.presensi.index') }}">
                <div class="ikon"><x-ikon nama="qr-code-scan" ukuran="21" /></div>
                <h3>Presensi</h3>
                <p>Roster mengajar, buka sesi, tampilkan QR, dan catat kehadiran siswa.</p>
            </a>
        </div>
    </div>
</section>

{{-- ---------- Data diri ---------- --}}
<section>
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Data Diri</div>
            <h2>Profil Guru</h2>
        </div>

        <div class="kartu">
            <div class="data-grid">
                <div class="data-item">
                    <div class="kunci">Nama</div>
                    <div class="nilai">{{ $guru->nama_guru }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">NIP</div>
                    <div class="nilai">{{ $guru->nip }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">Jenis Kelamin</div>
                    <div class="nilai">{{ $guru->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">Email</div>
                    <div class="nilai">{{ $guru->email ?? '—' }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">No. HP</div>
                    <div class="nilai">{{ $guru->no_hp ?? '—' }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">Mata Pelajaran</div>
                    <div class="nilai">{{ $kelasDiampu->pluck('nama_mapel')->unique()->implode(', ') ?: '—' }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">Kelas Diampu</div>
                    <div class="nilai">{{ $kelasDiampu->pluck('nama_kelas')->unique()->implode(', ') ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
