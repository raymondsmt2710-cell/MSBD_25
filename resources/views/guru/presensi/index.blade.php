@extends('layouts.presensi')
@section('judul', 'Roster Presensi')
@section('isi')
<h1>Roster Presensi</h1>
<p class="subjudul">
    Daftar jadwal mengajar Anda. Tombol <strong>Buat Presensi</strong> hanya aktif pada hari yang
    sesuai jadwal. Setelah dibuat, satu QR berlaku sampai jam selesai.
</p>

<div class="kartu">
    <div class="tabel-gulir">
        <table class="tabel-roster">
            <thead>
                <tr>
                    <th>Kelas</th><th>Hari</th><th>Jam</th><th>Mata Pelajaran</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($roster as $r)
                <tr @if ($r->hari === $hariIni) style="background:#eff6ff" @endif>
                    <td data-label="Kelas">{{ $r->nama_kelas }}</td>
                    <td data-label="Hari">
                        {{ ucfirst($r->hari) }}
                        @if ($r->hari === $hariIni)<span class="badge hadir">hari ini</span>@endif
                    </td>
                    <td data-label="Jam">{{ substr($r->jam_mulai, 0, 5) }} - {{ substr($r->jam_selesai, 0, 5) }}</td>
                    <td data-label="Mata Pelajaran">{{ $r->nama_mapel }}</td>
                    <td data-label="Aksi">
                        @if ($r->id_sesi)
                            <a class="btn" href="{{ route('guru.presensi.sesi', $r->id_sesi) }}">Lihat Sesi</a>
                        @elseif ($r->hari === $hariIni)
                            <form method="POST" action="{{ route('guru.presensi.buka', $r->id_jadwal) }}">@csrf
                                <button class="btn" type="submit">Buat Presensi</button>
                            </form>
                        @else
                            <span class="kecil">Bukan hari jadwal</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="kosong">Belum ada jadwal mengajar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
