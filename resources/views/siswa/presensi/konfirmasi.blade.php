@extends('layouts.app')
@section('judul', 'Konfirmasi Presensi')
@section('isi')
<div class="card" style="max-width:420px;margin:30px auto;text-align:center">
    @if ($valid)
        <h2>Konfirmasi Kehadiran</h2>
        <p>Tekan tombol di bawah untuk mencatat kehadiran Anda pada sesi ini.</p>

        <form method="POST" action="{{ route('siswa.presensi.scan', $kode) }}" id="form-presensi">@csrf
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <input type="hidden" name="akurasi_meter" id="akurasi_meter">
            <button class="btn" type="submit" id="tombol-hadir">Saya Hadir</button>
        </form>

        <p id="status-lokasi" style="font-size:12px;color:#6b7280;margin-bottom:0">
            Meminta izin lokasi...
        </p>

        <script>
            // Lokasi hanya dicatat sebagai bukti untuk guru. Presensi tetap dikirim
            // walau izin ditolak atau GPS tidak tersedia.
            const form = document.getElementById('form-presensi');
            const info = document.getElementById('status-lokasi');
            const tombol = document.getElementById('tombol-hadir');

            let lokasiSiap = false;
            let batasTunggu = null;

            function kirim() {
                form.submit();
            }

            function selesai(teks) {
                info.textContent = teks;
                lokasiSiap = true;
                tombol.disabled = false;
                tombol.textContent = 'Saya Hadir';
                if (batasTunggu) { clearTimeout(batasTunggu); }
            }

            if (!navigator.geolocation) {
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
    @else
        <h2>QR Tidak Berlaku</h2>
        <p>QR sudah kedaluwarsa, atau sesi sudah ditutup. Pindai QR yang ditampilkan guru.</p>
        <a class="btn abu" href="{{ route('siswa.presensi.riwayat') }}">Lihat Riwayat</a>
    @endif
</div>
@endsection
