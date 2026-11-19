@extends('layouts.presensi')
@section('judul', 'Konfirmasi Presensi')
@section('isi')
<h1>Konfirmasi Kehadiran</h1>
<p class="subjudul">Periksa kembali sebelum mencatat kehadiran Anda.</p>

@if ($valid)
    <div class="kartu" style="max-width:460px">
        <p style="font-size:13px;color:#4b5563;line-height:1.7;margin-top:0">
            Tekan tombol di bawah untuk mencatat kehadiran Anda pada sesi ini.
            Lokasi akan diminta sebagai bukti kehadiran; jika izin ditolak, presensi tetap tercatat.
        </p>

        <form method="POST" action="{{ route('siswa.presensi.scan', $kode) }}" id="form-presensi">@csrf
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <input type="hidden" name="akurasi_meter" id="akurasi_meter">
            <button class="btn" type="submit" id="tombol-hadir">Saya Hadir</button>
        </form>

        <p class="kecil" id="status-lokasi" style="margin:10px 0 0">Meminta izin lokasi...</p>

        <div class="info" id="info-lokasi-http" style="display:none;margin-top:12px;background:#fef9c3;color:#854d0e">
            <strong>Lokasi tidak dapat diambil pada alamat <code>http://</code>.</strong>
            Browser hanya mengizinkan lokasi pada <code>https://</code> atau <code>localhost</code>.
            Presensi tetap tercatat, hanya kolom lokasi yang kosong.
        </div>

        <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f1f2f4">
            <a class="btn garis" href="{{ route('guru.presensi.index') }}">&larr; Kembali</a>
        </div>
    </div>

    @push('skrip')
    <script>
        // Lokasi hanya dicatat sebagai bukti untuk guru. Presensi tetap dikirim
        // walau izin ditolak atau GPS tidak tersedia.
        const form = document.getElementById('form-presensi');
        const info = document.getElementById('status-lokasi');
        const tombol = document.getElementById('tombol-hadir');

        let batasTunggu = null;

        // Browser memblokir geolocation pada http:// (kecuali localhost).
        const konteksAman = window.isSecureContext ||
            location.hostname === 'localhost' ||
            location.hostname === '127.0.0.1';

        function selesai(teks) {
            info.textContent = teks;
            tombol.disabled = false;
            tombol.textContent = 'Saya Hadir';
            if (batasTunggu) { clearTimeout(batasTunggu); }
        }

        if (!konteksAman) {
            document.getElementById('info-lokasi-http').style.display = '';
            selesai('Lokasi tidak tersedia pada alamat http://. Presensi tetap dapat dikirim.');
        } else if (!navigator.geolocation) {
            selesai('Perangkat tidak mendukung lokasi. Presensi tetap dapat dikirim.');
        } else {
            tombol.disabled = true;
            tombol.textContent = 'Mengambil lokasi...';

            // Jangan menunggu terlalu lama: 6 detik lalu kirim tanpa lokasi.
            batasTunggu = setTimeout(function () {
                selesai('Lokasi tidak didapat dalam 6 detik. Presensi tetap dapat dikirim.');
            }, 6000);

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    document.getElementById('latitude').value = pos.coords.latitude.toFixed(7);
                    document.getElementById('longitude').value = pos.coords.longitude.toFixed(7);
                    if (pos.coords.accuracy) {
                        document.getElementById('akurasi_meter').value = pos.coords.accuracy.toFixed(2);
                    }
                    selesai('Lokasi tercatat (akurasi \u00b1' + Math.round(pos.coords.accuracy || 0) + ' m).');
                },
                function () {
                    selesai('Izin lokasi ditolak. Presensi tetap dapat dikirim.');
                },
                {enableHighAccuracy: true, timeout: 6000, maximumAge: 0}
            );
        }
    </script>
    @endpush
@else
    <div class="kartu" style="max-width:460px">
        <div class="err" style="margin-bottom:14px">
            QR sudah kedaluwarsa, atau sesi sudah ditutup. Pindai QR yang ditampilkan guru.
        </div>
        <div class="aksi-baris">
            <a class="btn" href="{{ route('siswa.presensi.pindai') }}">Pindai Ulang</a>
            <a class="btn abu" href="{{ route('guru.presensi.index') }}">Kembali</a>
        </div>
    </div>
@endif
@endsection
