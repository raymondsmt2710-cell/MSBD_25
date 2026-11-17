@extends('layouts.app')
@section('judul', 'Sesi Presensi')
@section('isi')
<div class="card">
    <h2>{{ $sesi->nama_mapel }} - {{ $sesi->nama_kelas }}</h2>
    <p>
        {{ \Carbon\Carbon::parse($sesi->tanggal)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
        &middot; {{ substr($sesi->jam_mulai, 0, 5) }} - {{ substr($sesi->jam_selesai, 0, 5) }}
        &middot; Status: <strong>{{ $sesi->status }}</strong>
    </p>

    @if ($sesi->status === 'dibuka')
        <div style="display:flex;gap:24px;align-items:center;flex-wrap:wrap">
            <div id="qr" style="width:320px;height:320px;background:#fff">
                @if ($qrSvg)
                    {!! $qrSvg !!}
                @else
                    <span style="color:#b91c1c;font-size:14px">QR belum tersedia. Muat ulang halaman.</span>
                @endif
            </div>
            <div>
                <p>Siswa memindai QR ini. QR berlaku sampai jam selesai dan tidak berganti.</p>
                @if ($token)
                    <p style="font-size:13px;color:#6b7280;margin-top:0">
                        Berlaku sampai {{ \Carbon\Carbon::parse($token->kedaluwarsa_pada)->format('H:i') }}
                    </p>
                @endif
                <p style="font-size:32px;margin:0">
                    <span id="hadir">{{ $siswa->where('status', 'hadir')->count() }}</span> hadir
                    &middot; <span id="belum">{{ $siswa->whereNull('status')->count() }}</span> belum
                </p>
                <form method="POST" action="{{ route('guru.presensi.tutup', $sesi->id_sesi) }}"
                      onsubmit="return confirm('Tutup sesi? Siswa yang belum tercatat akan otomatis alpa/izin/sakit.')">@csrf
                    <button class="btn merah" type="submit">Tutup Sesi</button>
                </form>
            </div>
        </div>
    @endif
</div>

<div class="card">
    <h3>Daftar Kehadiran</h3>
    <table>
        <tr><th>No</th><th>NIS</th><th>Nama</th><th>Status</th><th>Metode</th><th>Waktu</th><th>Lokasi</th><th>Koreksi</th></tr>
        @foreach ($siswa as $s)
            <tr>
                <td>{{ $s->no_absen }}</td><td>{{ $s->nis }}</td><td>{{ $s->nama_siswa }}</td>
                <td><span class="badge {{ $s->status }}">{{ $s->status ?? 'belum' }}</span></td>
                <td>{{ $s->metode ?? '-' }}</td><td>{{ $s->waktu_presensi ?? '-' }}</td>
                <td style="font-size:12px">
                    @if ($s->latitude && $s->longitude)
                        <a href="https://www.google.com/maps?q={{ $s->latitude }},{{ $s->longitude }}"
                           target="_blank" rel="noopener">
                            {{ number_format($s->latitude, 5) }}, {{ number_format($s->longitude, 5) }}
                        </a>
                        @if ($s->akurasi_meter)
                            <span style="color:#9ca3af">(&plusmn;{{ round($s->akurasi_meter) }} m)</span>
                        @endif
                    @else
                        <span style="color:#9ca3af">tidak tercatat</span>
                    @endif
                </td>
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
    // Hanya memperbarui angka hadir/belum. QR tidak diambil ulang karena statis.
    const url = @json(route('guru.presensi.qr', $sesi->id_sesi));
    const elHadir = document.getElementById('hadir');
    const elBelum = document.getElementById('belum');

    async function muat() {
        try {
            const r = await fetch(url, {headers: {'Accept': 'application/json'}});
            const d = await r.json();
            if (d.status === 'OK') {
                if (Number(elHadir.textContent) !== d.hadir) { elHadir.textContent = d.hadir; }
                if (Number(elBelum.textContent) !== d.belum) { elBelum.textContent = d.belum; }
            } else if (d.status === 'DITUTUP') {
                location.reload();
            }
        } catch (e) { /* abaikan, coba lagi pada putaran berikutnya */ }
    }
    setInterval(muat, 4000);
</script>
@endif
@endsection
