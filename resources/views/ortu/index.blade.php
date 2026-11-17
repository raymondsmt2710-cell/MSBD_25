@extends('layouts.pemilihan')
@section('judul', 'Portal Orang Tua')
@section('isi')
<h1>Portal Pemantauan Orang Tua/Wali</h1>
<p class="subjudul">
    Selamat datang, <strong>{{ $orangTua->nama ?? auth()->user()->username }}</strong>.
    Halaman ini hanya untuk melihat; tidak ada data yang bisa diubah dari sini.
</p>

@if ($anak->isEmpty())
    <div class="kartu">
        <div class="kosong">
            Belum ada data anak yang terhubung ke akun ini. Hubungi administrator sekolah.
        </div>
    </div>
@else
    <div class="grid grid-2">
        @foreach ($anak as $a)
            <div class="kartu">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <div>
                        <h2 style="margin-bottom:6px">{{ $a->nama_siswa }}</h2>
                        <div class="kecil">
                            NIS {{ $a->nis }} &middot;
                            Kelas {{ $a->nama_kelas ?? 'belum terdaftar' }}
                        </div>
                        <div style="margin-top:8px">
                            <span class="pill">
                                {{ ucfirst($a->hubungan) }}{{ $a->is_kontak_utama ? ' · kontak utama' : '' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="meta" style="margin-top:16px">
                    <div class="stat">
                        <span class="angka">{{ number_format($a->ringkasan['persen'], 2) }}%</span>
                        <span class="label">Kehadiran</span>
                    </div>
                </div>

                <div class="bar" style="margin-top:10px">
                    <span style="width:{{ min(100, max(0, (float) $a->ringkasan['persen'])) }}%"></span>
                </div>

                <p style="margin:12px 0 0;font-size:13px">
                    Hadir {{ $a->ringkasan['hadir'] }} &middot;
                    Izin {{ $a->ringkasan['izin'] }} &middot;
                    Sakit {{ $a->ringkasan['sakit'] }} &middot;
                    Alpa {{ $a->ringkasan['alpa'] }}
                </p>

                <div style="margin-top:14px">
                    <a class="btn" href="{{ route('ortu.anak', $a->id_siswa) }}">Lihat detail</a>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
