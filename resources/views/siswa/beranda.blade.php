@extends('layouts.siswa')

@section('judul', 'Beranda Siswa')

@section('isi')

{{-- ---------- Hero ---------- --}}
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

{{-- ---------- Statistik kehadiran ---------- --}}
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

{{-- ---------- Menu cepat ---------- --}}
<section>
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Menu Cepat</div>
            <h2>Mau ke mana?</h2>
            <p>Empat pintu masuk ke fitur yang tersedia untuk akun siswa.</p>
        </div>

        <div class="ikon-grid">
            <a class="ikon-kartu" href="{{ route('siswa.presensi.riwayat') }}#ringkasan">
                <div class="ikon"><x-ikon nama="qr-code-scan" ukuran="21" /></div>
                <h3>Presensi</h3>
                <p>Ringkasan kehadiran dan cara memindai QR sesi guru.</p>
            </a>

            <a class="ikon-kartu" href="{{ route('siswa.presensi.riwayat') }}#roster">
                <div class="ikon"><x-ikon nama="calendar-week" ukuran="21" /></div>
                <h3>Roster</h3>
                <p>Jadwal pelajaran pekan ini beserta status kehadiran.</p>
            </a>

            <a class="ikon-kartu" href="{{ route('siswa.perangkat') }}">
                <div class="ikon"><x-ikon nama="diagram-3" ukuran="21" /></div>
                <h3>Perangkat Kelas &amp; Organisasi</h3>
                <p>Susunan pengurus kelas dan pengurus organisasi.</p>
            </a>

            <a class="ikon-kartu" href="{{ route('siswa.pemilihan.index') }}">
                <div class="ikon"><x-ikon nama="check2-square" ukuran="21" /></div>
                <h3>Pemilihan</h3>
                <p>Ikuti pemilihan pengurus organisasi. Suara Anda rahasia.</p>
            </a>
        </div>
    </div>
</section>

{{-- ---------- Data diri ---------- --}}
<section>
    <div class="wrap">
        <div class="judul-bagian">
            <div class="kicker">Data Diri</div>
            <h2>Profil Siswa</h2>
        </div>

        <div class="kartu">
            <div class="data-grid">
                <div class="data-item">
                    <div class="kunci">Nama</div>
                    <div class="nilai">{{ $siswa->nama_siswa }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">NIS</div>
                    <div class="nilai">{{ $siswa->nis }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">NISN</div>
                    <div class="nilai">{{ $siswa->nisn ?? '—' }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">Kelas</div>
                    <div class="nilai">{{ $kelas }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">Jenis Kelamin</div>
                    <div class="nilai">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">Tempat, Tanggal Lahir</div>
                    <div class="nilai">
                        {{ $siswa->tempat_lahir ?? '—' }}@if ($siswa->tanggal_lahir),
                            {{ \Illuminate\Support\Carbon::parse($siswa->tanggal_lahir)->locale('id')->isoFormat('D MMMM YYYY') }}
                        @endif
                    </div>
                </div>
                <div class="data-item">
                    <div class="kunci">No. HP</div>
                    <div class="nilai">{{ $siswa->no_hp ?? '—' }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">Alamat</div>
                    <div class="nilai">{{ $siswa->alamat ?? '—' }}</div>
                </div>
                <div class="data-item">
                    <div class="kunci">Status</div>
                    <div class="nilai"><span class="pill">{{ $siswa->status }}</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
