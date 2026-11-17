@extends('layouts.presensi')
@section('judul', 'Pindai QR')
@section('isi')
<h1>Pindai QR Presensi</h1>
<p class="subjudul">Arahkan kamera ke QR di layar guru, atau pilih foto QR dari galeri.</p>

<div class="info">
    <strong>Kamera tidak bisa dipakai?</strong>
    Minta teman Anda memotret QR di layar guru, lalu kirim fotonya. Simpan ke galeri, kemudian
    gunakan tombol <strong>Pilih Foto dari Galeri</strong> di bawah. Hasilnya sama saja.
</div>

<div class="info" id="peringatan-http" style="display:none;background:#fef9c3;color:#854d0e">
    <strong>Kamera diblokir browser pada alamat <code>http://</code>.</strong>
    Browser hanya mengizinkan kamera pada <code>https://</code> atau <code>localhost</code>.
    Di halaman ini, gunakan <strong>Pilih Foto dari Galeri</strong> — caranya sama, hasilnya sama.
</div>

<div class="kartu">
    <div class="pemindai">
        <div id="pembaca"></div>
        <div class="pemindai-kosong" id="kosong">
            Kamera belum dinyalakan.<br>Tekan <strong>Nyalakan Kamera</strong> di bawah.
        </div>
        <div class="bingkai" id="bingkai">
            <div class="garis-pindai"></div>
        </div>
    </div>

    <div class="aksi-baris" style="margin-top:14px">
        <button class="btn" type="button" id="btn-kamera">Nyalakan Kamera</button>
        <button class="btn abu" type="button" id="btn-stop" disabled>Stop Kamera</button>
    </div>

    <div class="pemisah">atau</div>

    <label for="berkas">Pilih foto QR dari galeri</label>
    <input type="file" id="berkas" accept="image/*">
    <p class="kecil" id="pesan" style="margin:8px 0 0">
        Foto QR yang jelas dan tidak terpotong lebih mudah dikenali.
    </p>
</div>

<div class="kartu">
    <h3>Petunjuk</h3>
    <ul style="margin:0;padding-left:18px;font-size:12.5px;color:#4b5563;line-height:1.9">
        <li>Pastikan Anda sudah masuk dengan akun siswa sendiri.</li>
        <li>QR hanya berlaku selama jam sesi yang dibuka guru.</li>
        <li>Setelah QR terbaca, Anda masih perlu menekan tombol <strong>Saya Hadir</strong>.</li>
        <li>Kamera hanya dapat dipakai pada alamat <code>https://</code> atau <code>localhost</code>.
            Jika dibuka lewat alamat IP biasa, gunakan pilihan galeri.</li>
    </ul>
</div>
@endsection

@push('skrip')
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script>
(function () {
    const elemenPembaca = document.getElementById('pembaca');
    const elemenKosong  = document.getElementById('kosong');
    const elemenBingkai = document.getElementById('bingkai');
    const btnKamera     = document.getElementById('btn-kamera');
    const btnStop       = document.getElementById('btn-stop');
    const inputBerkas   = document.getElementById('berkas');
    const pesan         = document.getElementById('pesan');

    const urlKonfirmasi = @json(url('/dashboard/presensi/scan'));

    let pemindai = null;
    let sedangJalan = false;

    // Browser memblokir kamera pada http:// (kecuali localhost).
    const konteksAman = window.isSecureContext ||
        location.hostname === 'localhost' ||
        location.hostname === '127.0.0.1';

    if (!konteksAman) {
        document.getElementById('peringatan-http').style.display = '';
        btnKamera.disabled = true;
        btnKamera.textContent = 'Kamera tidak tersedia';
        tulisPesan('Gunakan pilihan galeri di bawah untuk memindai QR.', 'ok');
        elemenKosong.innerHTML = 'Kamera diblokir browser pada alamat http://.<br>Gunakan <strong>Pilih Foto dari Galeri</strong>.';
    }

    function tulisPesan(teks, jenis) {
        pesan.textContent = teks;
        pesan.style.color = jenis === 'error' ? '#991b1b' : (jenis === 'ok' ? '#166534' : '#9ca3af');
    }

    /** Hasil pemindaian berupa URL. Ambil kode QR di segmen terakhir. */
    function bukaHasil(teks) {
        if (!teks) {
            tulisPesan('QR tidak terbaca. Coba lagi dengan gambar yang lebih jelas.', 'error');
            return;
        }

        let kode = teks.trim();
        try {
            const u = new URL(kode);
            const bagian = u.pathname.split('/').filter(Boolean);
            kode = bagian[bagian.length - 1] || '';
        } catch (e) {
            // Bukan URL: mungkin hanya kode mentah. Pakai apa adanya.
        }

        // Kode token presensi selalu 64 karakter hex.
        if (!/^[a-f0-9]{64}$/i.test(kode)) {
            tulisPesan('QR ini bukan QR presensi. Pastikan memindai QR di layar guru.', 'error');
            return;
        }

        tulisPesan('QR terbaca. Membuka halaman konfirmasi...', 'ok');
        window.location.href = urlKonfirmasi + '/' + kode;
    }

    async function mulaiKamera() {
        if (!window.Html5Qrcode) {
            tulisPesan('Pustaka pemindai gagal dimuat. Gunakan pilihan galeri.', 'error');
            return;
        }

        if (!pemindai) {
            pemindai = new Html5Qrcode('pembaca', { verbose: false });
        }

        elemenKosong.style.display = 'none';
        elemenBingkai.classList.add('tampil');
        btnKamera.disabled = true;
        tulisPesan('Meminta izin kamera...');

        try {
            await pemindai.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 240, height: 240 } },
                function (teks) {
                    if (sedangJalan) { hentikanKamera(); }
                    bukaHasil(teks);
                },
                function () { /* gagal membaca satu frame: abaikan */ }
            );
            sedangJalan = true;
            btnStop.disabled = false;
            tulisPesan('Kamera aktif. Arahkan ke QR di layar guru.');
        } catch (e) {
            elemenKosong.style.display = '';
            elemenBingkai.classList.remove('tampil');
            btnKamera.disabled = false;
            tulisPesan('Kamera tidak dapat dipakai: ' + (e && e.message ? e.message : e) +
                '. Gunakan pilihan galeri di bawah.', 'error');
        }
    }

    async function hentikanKamera() {
        if (!pemindai || !sedangJalan) { return; }
        try { await pemindai.stop(); } catch (e) { /* sudah berhenti */ }
        sedangJalan = false;
        btnKamera.disabled = false;
        btnStop.disabled = true;
        elemenBingkai.classList.remove('tampil');
        elemenKosong.style.display = '';
        elemenKosong.innerHTML = 'Kamera dimatikan.';
    }

    btnKamera.addEventListener('click', mulaiKamera);
    btnStop.addEventListener('click', hentikanKamera);

    // Pindai dari foto galeri (untuk kamera rusak).
    inputBerkas.addEventListener('change', async function () {
        const berkas = inputBerkas.files && inputBerkas.files[0];
        if (!berkas) { return; }

        if (!window.Html5Qrcode) {
            tulisPesan('Pustaka pemindai gagal dimuat. Muat ulang halaman.', 'error');
            return;
        }

        if (sedangJalan) { await hentikanKamera(); }

        tulisPesan('Membaca foto...');

        // Pemindai sementara yang tidak memakai kamera.
        const pembacaBerkas = new Html5Qrcode('pembaca', { verbose: false });

        try {
            const hasil = await pembacaBerkas.scanFile(berkas, false);
            bukaHasil(hasil);
        } catch (e) {
            tulisPesan('QR tidak dikenali pada foto itu. Coba foto yang lebih jelas dan tidak terpotong.', 'error');
        } finally {
            try { pembacaBerkas.clear(); } catch (e) { /* abaikan */ }
            elemenKosong.style.display = '';
            elemenKosong.innerHTML = 'Kamera belum dinyalakan.';
        }
    });
})();
</script>
@endpush
