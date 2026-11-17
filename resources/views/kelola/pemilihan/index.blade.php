@extends('layouts.pemilihan')
@section('judul', 'Kelola Pemilihan')
@section('isi')
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <h1>Kelola Pemilihan</h1>
        <p class="subjudul">Buat pemilihan, atur kandidat, daftarkan pemilih, lalu buka pemungutan suara.</p>
    </div>
    <a class="btn" href="{{ route('kelola.pemilihan.form') }}">+ Buat Pemilihan</a>
</div>

<div class="kartu">
    @if ($pemilihan->isEmpty())
        <div class="kosong">Belum ada pemilihan. Mulai dengan menekan "Buat Pemilihan".</div>
    @else
        <table>
            <thead>
            <tr>
                <th>Judul</th>
                <th>Organisasi</th>
                <th>Tahun Ajaran</th>
                <th>Status</th>
                <th>Waktu</th>
                <th>Kandidat</th>
                <th>Pemilih</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($pemilihan as $p)
                <tr>
                    <td><strong>{{ $p->judul }}</strong></td>
                    <td>{{ $p->nama_organisasi }}</td>
                    <td>{{ $p->tahun_ajaran }}</td>
                    <td><span class="badge {{ $p->status }}">{{ $p->status }}</span></td>
                    <td class="kecil">
                        {{ \Illuminate\Support\Carbon::parse($p->waktu_mulai)->format('d M Y H:i') }}<br>
                        s/d {{ \Illuminate\Support\Carbon::parse($p->waktu_selesai)->format('d M Y H:i') }}
                    </td>
                    <td>{{ $p->jumlah_kandidat }}</td>
                    <td>{{ $p->jumlah_pemilih }}</td>
                    <td>
                        <div class="aksi-baris">
                            <a class="btn garis" href="{{ route('kelola.pemilihan.monitor', $p->id_pemilihan) }}">Monitor</a>
                            <a class="btn garis" href="{{ route('kelola.pemilihan.kandidat', $p->id_pemilihan) }}">Kandidat</a>
                            <a class="btn garis" href="{{ route('kelola.pemilihan.pemilih', $p->id_pemilihan) }}">Pemilih</a>
                            @if ($p->status === 'draf')
                                <a class="btn garis" href="{{ route('kelola.pemilihan.ubah', $p->id_pemilihan) }}">Ubah</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="paginasi">{{ $pemilihan->links() }}</div>
    @endif
</div>
@endsection
