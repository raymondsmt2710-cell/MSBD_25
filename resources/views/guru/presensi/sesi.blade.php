@extends('layouts.app')
@section('judul', 'Sesi Presensi')
@section('isi')
<div class="card">
    <h2>{{ $sesi->nama_mapel }} - {{ $sesi->nama_kelas }}</h2>
    <p>Tanggal {{ $sesi->tanggal }} · Status: <strong>{{ $sesi->status }}</strong></p>

    @if ($sesi->status === 'dibuka')
        <div style="display:flex;gap:24px;align-items:center;flex-wrap:wrap">
            <div id="qr" style="width:320px;height:320px;background:#fff">Memuat QR...</div>
            <div>
                <p>Siswa memindai QR ini. Setiap QR hanya berlaku sekali dan akan berganti otomatis.</p>
                <p style="font-size:32px;margin:0"><span id="hadir">{{ $siswa->where('status', 'hadir')->count() }}</span> hadir</p>
                <form method="POST" action="{{ route('guru.presensi.tutup', $sesi->id_sesi) }}"
                      onsubmit="return confirm('Tutup sesi? Siswa yang belum tercatat akan otomatis izin/sakit/alpa.')">@csrf
                    <button class="btn merah" type="submit">Tutup Sesi</button>
                </form>
            </div>
        </div>
    @endif
</div>

<div class="card">
    <h3>Daftar Kehadiran</h3>
    <table>
        <tr><th>No</th><th>NIS</th><th>Nama</th><th>Status</th><th>Metode</th><th>Waktu</th><th>Koreksi</th></tr>
        @foreach ($siswa as $s)
            <tr>
                <td>{{ $s->no_absen }}</td><td>{{ $s->nis }}</td><td>{{ $s->nama_siswa }}</td>
                <td><span class="badge {{ $s->status }}">{{ $s->status ?? 'belum' }}</span></td>
                <td>{{ $s->metode ?? '-' }}</td><td>{{ $s->waktu_presensi ?? '-' }}</td>
                <td>
                    <form method="POST" action="{{ route('guru.presensi.manual', $sesi->id_sesi) }}" style="display:flex;gap:4px">@csrf
                        <input type="hidden" name="id_siswa" value="{{ $s->id_siswa }}">
                        <select name="status">
                            @foreach (['hadir', 'izin', 'sakit', 'alpa'] as $st)
                                <option value="{{ $st }}" @selected($s->status === $st)>{{ $st }}</option>
                            @endforeach
                        </select>
                        <button class="btn abu" type="submit">Simpan</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
</div>

@if ($sesi->status === 'dibuka')
<script>
    const url = @json(route('guru.presensi.qr', $sesi->id_sesi));
    const kotakQr = document.getElementById('qr');
    const hadir = document.getElementById('hadir');
    let kodeTerakhir = null;

    async function muat() {
        try {
            const r = await fetch(url, {headers: {'Accept': 'application/json'}});
            const d = await r.json();
            if (d.status === 'OK') {
                if (d.kode !== kodeTerakhir) { kotakQr.innerHTML = d.svg; kodeTerakhir = d.kode; }
                if (Number(hadir.textContent) !== d.hadir) { hadir.textContent = d.hadir; setTimeout(() => location.reload(), 1200); }
            } else if (d.status === 'DITUTUP') {
                location.reload();
            }
        } catch (e) { /* abaikan, coba lagi pada putaran berikutnya */ }
    }
    muat();
    setInterval(muat, 3000);
</script>
@endif
@endsection
