@extends('layouts.app')
@section('judul', 'Portal Orang Tua')
@section('isi')
<div class="card">
    <h2 style="margin:0 0 4px">Portal Pemantauan Orang Tua/Wali</h2>
    <p style="margin:0;color:#6b7280;font-size:14px">
        Selamat datang, <strong>{{ $orangTua->nama ?? auth()->user()->username }}</strong>.
        Halaman ini hanya untuk melihat; tidak ada data yang bisa diubah dari sini.
    </p>
</div>

@forelse ($anak as $a)
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
            <div>
                <h3 style="margin:0 0 4px">{{ $a->nama_siswa }}</h3>
                <p style="margin:0;font-size:13px;color:#6b7280">
                    NIS {{ $a->nis }} &middot;
                    Kelas {{ $a->nama_kelas ?? 'belum terdaftar' }} &middot;
                    {{ ucfirst($a->hubungan) }}{{ $a->is_kontak_utama ? ' (kontak utama)' : '' }}
                </p>
            </div>
            <a class="btn" href="{{ route('ortu.anak', $a->id_siswa) }}" style="text-decoration:none">
                Lihat detail
            </a>
        </div>

        <p style="font-size:26px;margin:12px 0 2px">{{ number_format($a->ringkasan['persen'], 2) }}%</p>
        <p style="margin:0;font-size:13px;color:#6b7280">Kehadiran</p>
        <p style="margin:8px 0 0;font-size:14px">
            Hadir {{ $a->ringkasan['hadir'] }} &middot; Izin {{ $a->ringkasan['izin'] }} &middot;
            Sakit {{ $a->ringkasan['sakit'] }} &middot; Alpa {{ $a->ringkasan['alpa'] }}
        </p>
    </div>
@empty
    <div class="card">
        <p style="margin:0">Belum ada data anak yang terhubung ke akun ini. Hubungi administrator sekolah.</p>
    </div>
@endforelse
@endsection
