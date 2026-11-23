@extends('layouts.presensi')
@section('judul', 'Sesi Presensi')
@section('isi')
<h1>{{ $sesi->nama_mapel }} - {{ $sesi->nama_kelas }}</h1>
<p class="subjudul">
    {{ \Carbon\Carbon::parse($sesi->tanggal)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
    &middot; {{ substr($sesi->jam_mulai, 0, 5) }} - {{ substr($sesi->jam_selesai, 0, 5) }}
    &middot; Status: <strong>{{ $sesi->status }}</strong>
</p>

@if ($sesi->status === 'dibuka')
    <div class="kartu">
        <div class="grid grid-2" style="align-items:center">
            <div id="qr" style="background:#fff;display:flex;align-items:center;justify-content:center;min-height:280px">
                @if ($qrSvg)
                    {!! $qrSvg !!}
                @else
                    <span style="color:#b91c1c;font-size:13px">QR belum tersedia. Muat ulang halaman.</span>
                @endif
            </div>

            <div>
                <p style="font-size:13px;color:#4b5563;line-height:1.7;margin-top:0">
                    Siswa memindai QR ini. QR berlaku sampai jam selesai dan tidak berganti.
                </p>
                @if ($token)
                    <p class="kecil" style="margin-top:0">
                        Berlaku sampai {{ \Carbon\Carbon::parse($token->kedaluwarsa_pada)->format('H:i') }}
                    </p>
                @endif

                <div class="grid grid-2" style="margin-bottom:14px">
                    <div class="stat">
                        <span class="angka" id="hadir">{{ $siswa->where('status', 'hadir')->count() }}</span>
                        <span class="label">Hadir</span>
                    </div>
                    <div class="stat">
                        <span class="angka" id="belum">{{ $siswa->whereNull('status')->count() }}</span>
                        <span class="label">Belum</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('guru.presensi.tutup', $sesi->id_sesi) }}"
                      onsubmit="return confirm('Tutup sesi? Siswa yang belum tercatat akan otomatis alpa/izin/sakit.')">@csrf
                    <button class="btn merah" type="submit">Tutup Sesi</button>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="kartu">
    <h3>Daftar Kehadiran</h3>
    <div class="tabel-gulir">
        <table class="tabel-roster">
            <thead>
                <tr><th>No</th><th>NIS</th><th>Nama</th><th>Status</th><th>Metode</th><th>Waktu</th><th>Lokasi</th><th>Koreksi</th></tr>
            </thead>
            <tbody>
            @foreach ($siswa as $s)
                <tr>
                    <td data-label="No">{{ $s->no_absen }}</td>
                    <td data-label="NIS">{{ $s->nis }}</td>
                    <td data-label="Nama">{{ $s->nama_siswa }}</td>
                    <td data-label="Status"><span class="badge {{ $s->status ?? 'belum' }}">{{ $s->status ?? 'belum' }}</span></td>
                    <td data-label="Metode">{{ $s->metode ?? '-' }}</td>
                    <td data-label="Waktu">{{ $s->waktu_presensi ?? '-' }}</td>
                    <td data-label="Lokasi" style="font-size:12px">
                        @if ($s->latitude && $s->longitude)
                            <a href="https://www.google.com/maps?q={{ $s->latitude }},{{ $s->longitude }}"
                               target="_blank" rel="noopener" style="color:#09656F;font-weight:600">
                                {{ number_format($s->latitude, 5) }}, {{ number_format($s->longitude, 5) }}
                            </a>
                            @if ($s->akurasi_meter)
                                <span class="kecil">(&plusmn;{{ round($s->akurasi_meter) }} m)</span>
                            @endif
                        @else
                            <span class="kecil">tidak tercatat</span>
                        @endif
                    </td>
                    <td data-label="Koreksi">
                        <form method="POST" action="{{ route('guru.presensi.manual', $sesi->id_sesi) }}" style="display:flex;gap:4px">@csrf
                            <input type="hidden" name="id_siswa" value="{{ $s->id_siswa }}">
                            <select name="status" style="width:auto">
                                @foreach (['hadir', 'izin', 'sakit', 'alpa'] as $st)
                                    <option value="{{ $st }}" @selected($s->status === $st)>{{ $st }}</option>
                                @endforeach
                            </select>
                            <button class="btn abu" type="submit">Simpan</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@if ($sesi->status === 'dibuka')
@push('skrip')
<script>

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
        } catch (e) {  }
    }
    setInterval(muat, 4000);
</script>
@endpush
@endif
@endsection
