<?php

use App\Enums\Role;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DashboardGuruController;
use App\Http\Controllers\DashboardSiswaController;
use App\Http\Controllers\Denah\DenahController;
use App\Http\Controllers\Denah\DenahKelasController;
use App\Http\Controllers\Denah\RuanganSekolahController;
use App\Http\Controllers\Kas\ExportKasController;
use App\Http\Controllers\Kas\IuranKasController;
use App\Http\Controllers\Kas\KasController;
use App\Http\Controllers\Kas\TransaksiKasController;
use App\Http\Controllers\KelolaPemilihanController;
use App\Http\Controllers\PortalOrangTuaController;
use App\Http\Controllers\PresensiGuruController;
use App\Http\Controllers\PresensiSiswaController;
use App\Http\Controllers\SiswaPemilihanController;
use App\Support\PolaRute;
use Illuminate\Support\Facades\Route;

// Halaman awal untuk tamu. Pengguna yang sudah login langsung diarahkan sesuai peran.
Route::get('/', [BerandaController::class, 'index'])->name('beranda');

Route::get('/login', [AuthController::class, 'form'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses')->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ---- Fitur 1 lama: Presensi QR (URL lama tetap dipertahankan) ----
Route::middleware(['auth', 'role:guru'])->prefix('guru/presensi')->name('guru.presensi.')->group(function () {
    Route::get('/', [PresensiGuruController::class, 'index'])->name('index');
    Route::post('/jadwal/{idJadwal}/buka', [PresensiGuruController::class, 'buka'])->name('buka');
    Route::get('/sesi/{idSesi}', [PresensiGuruController::class, 'tampil'])->name('sesi');
    Route::get('/sesi/{idSesi}/qr', [PresensiGuruController::class, 'qr'])->name('qr');
    Route::post('/sesi/{idSesi}/tutup', [PresensiGuruController::class, 'tutup'])->name('tutup');
    Route::post('/sesi/{idSesi}/manual', [PresensiGuruController::class, 'manual'])->name('manual');
});

Route::middleware(['auth', 'role:siswa'])->prefix('siswa/presensi')->name('siswa.presensi.')->group(function () {
    Route::get('/riwayat', [PresensiSiswaController::class, 'riwayat'])->name('riwayat');
    Route::get('/scan/{kode}', [PresensiSiswaController::class, 'konfirmasi'])->name('konfirmasi');
    Route::post('/scan/{kode}', [PresensiSiswaController::class, 'scan'])->name('scan')->middleware('throttle:10,1');
});

// ---- Dashboard siswa & guru (hanya-baca) ----
Route::middleware(['auth', 'role:siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/beranda', [DashboardSiswaController::class, 'index'])->name('beranda');
    Route::get('/perangkat', [DashboardSiswaController::class, 'perangkat'])->name('perangkat');
});
Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/beranda', [DashboardGuruController::class, 'index'])->name('beranda');
});

// ---- Fitur 1: Presensi QR ----
// Semua halaman presensi berada di bawah /dashboard/presensi.
// Halaman masuk (index) berbeda isi menurut peran: siswa melihat dua pilihan,
// guru langsung melihat roster jadwal mengajarnya.
Route::middleware(['auth', 'role:guru,siswa'])->prefix('dashboard/presensi')->name('dashboard.presensi.')->group(function () {
    Route::get('/', [PresensiGuruController::class, 'masuk'])->name('index');

    // --- Siswa ---
    Route::middleware('role:siswa')->group(function () {
        Route::get('/riwayat', [PresensiSiswaController::class, 'riwayat'])->name('riwayat');
        Route::get('/pindai', [PresensiSiswaController::class, 'pindai'])->name('pindai');
        Route::get('/scan/{kode}', [PresensiSiswaController::class, 'konfirmasi'])->name('konfirmasi');
        Route::post('/scan/{kode}', [PresensiSiswaController::class, 'scan'])->name('scan')->middleware('throttle:10,1');
    });

    // --- Guru ---
    Route::middleware('role:guru')->group(function () {
        Route::post('/jadwal/{idJadwal}/buka', [PresensiGuruController::class, 'buka'])->name('buka');
        Route::get('/sesi/{idSesi}', [PresensiGuruController::class, 'tampil'])->name('sesi');
        Route::get('/sesi/{idSesi}/qr', [PresensiGuruController::class, 'qr'])->name('qr');
        Route::post('/sesi/{idSesi}/tutup', [PresensiGuruController::class, 'tutup'])->name('tutup');
        Route::post('/sesi/{idSesi}/manual', [PresensiGuruController::class, 'manual'])->name('manual');
    });
});

// ---- Fitur 7: Pemantauan orang tua/wali (hanya-baca, tidak ada rute tulis) ----
Route::middleware(['auth', 'role:orang_tua'])->prefix('ortu')->name('ortu.')->group(function () {
    Route::get('/', [PortalOrangTuaController::class, 'index'])->name('index');
    // PolaRute::ID mencegah id di luar rentang PHP_INT_MAX menjadi TypeError (500);
    // dengan batas ini masukan ngawur berhenti sebagai 404.
    Route::get('/anak/{idSiswa}', [PortalOrangTuaController::class, 'anak'])
        ->where('idSiswa', PolaRute::ID)->name('anak');
});

// ---- Fitur 3: Pemilihan suara elektronik (e-voting) ----
Route::middleware(['auth', 'role:siswa'])->prefix('siswa/pemilihan')->name('siswa.pemilihan.')->group(function () {
    Route::get('/', [SiswaPemilihanController::class, 'index'])->name('index');
    Route::get('/{id}/pilih', [SiswaPemilihanController::class, 'pilih'])->name('pilih');
    Route::post('/{id}/pilih', [SiswaPemilihanController::class, 'simpan'])->name('simpan')->middleware('throttle:10,1');
    Route::get('/{id}/hasil', [SiswaPemilihanController::class, 'hasil'])->name('hasil');
});

Route::middleware(['auth', 'role:admin,guru'])->prefix('kelola/pemilihan')->name('kelola.pemilihan.')->group(function () {
    Route::get('/', [KelolaPemilihanController::class, 'index'])->name('index');

    Route::get('/baru', [KelolaPemilihanController::class, 'form'])->name('form');
    Route::post('/baru', [KelolaPemilihanController::class, 'simpan'])->name('simpan');
    Route::get('/{id}/ubah', [KelolaPemilihanController::class, 'form'])->name('ubah');
    Route::post('/{id}/ubah', [KelolaPemilihanController::class, 'simpan'])->name('ubah.simpan');

    Route::get('/{id}/kandidat', [KelolaPemilihanController::class, 'kandidat'])->name('kandidat');
    Route::post('/{id}/kandidat', [KelolaPemilihanController::class, 'simpanKandidat'])->name('kandidat.simpan');
    Route::delete('/{id}/kandidat/{idKandidat}', [KelolaPemilihanController::class, 'hapusKandidat'])->name('kandidat.hapus');

    Route::get('/{id}/pemilih', [KelolaPemilihanController::class, 'pemilih'])->name('pemilih');
    Route::post('/{id}/pemilih', [KelolaPemilihanController::class, 'daftarkanPemilih'])->name('pemilih.daftarkan');
    Route::delete('/{id}/pemilih/{idSiswa}', [KelolaPemilihanController::class, 'hapusPemilih'])->name('pemilih.hapus');

    Route::get('/{id}/panitia', [KelolaPemilihanController::class, 'panitia'])->name('panitia');
    Route::post('/{id}/panitia', [KelolaPemilihanController::class, 'simpanPanitia'])->name('panitia.simpan');

    Route::get('/{id}/monitor', [KelolaPemilihanController::class, 'monitor'])->name('monitor');
    Route::get('/{id}/hasil', [KelolaPemilihanController::class, 'hasil'])->name('hasil');
    Route::post('/{id}/buka', [KelolaPemilihanController::class, 'buka'])->name('buka');
    Route::post('/{id}/tutup', [KelolaPemilihanController::class, 'tutup'])->name('tutup');
    Route::post('/{id}/kembalikan', [KelolaPemilihanController::class, 'kembalikan'])->name('kembalikan');

    Route::get('/{id}/log', [KelolaPemilihanController::class, 'log'])->name('log');
});

// ---- Fitur 4: Pencatatan kas kelas ----
Route::middleware('auth')->prefix('kas')->name('kas.')->group(function () {
    Route::get('/', [KasController::class, 'index'])->name('index');
    Route::get('/data', [KasController::class, 'data'])->name('data');
    Route::post('/transaksi', [TransaksiKasController::class, 'store'])->name('transaksi.store');
    Route::put('/transaksi/{transaksi}', [TransaksiKasController::class, 'update'])->name('transaksi.update');
    Route::delete('/transaksi/{transaksi}', [TransaksiKasController::class, 'batal'])->name('transaksi.batal');

    Route::post('/iuran/generate', [IuranKasController::class, 'generate'])->name('iuran.generate');
    Route::patch('/iuran/{iuran}/verifikasi', [IuranKasController::class, 'verifikasi'])->name('iuran.verifikasi');
    Route::patch('/iuran/{iuran}/batal', [IuranKasController::class, 'batal'])->name('iuran.batal');

    Route::get('/export', [ExportKasController::class, 'csv'])->name('export');
    Route::get('/cetak', [ExportKasController::class, 'cetak'])->name('cetak');
});

// ---- Fitur 6: Denah kelas dan area sekolah ----
Route::middleware('auth')->prefix('denah')->name('denah.')->group(function () {
    Route::get('/', [DenahController::class, 'index'])->name('index');
    Route::get('/kelas/{kelas}/data', [DenahKelasController::class, 'data'])->name('kelas.data');
    Route::put('/kelas/{kelas}', [DenahKelasController::class, 'simpan'])->name('kelas.simpan');
    Route::patch('/posisi/{posisi}', [DenahKelasController::class, 'pindah'])->name('posisi.pindah');
    Route::post('/kelas/{kelas}/swap', [DenahKelasController::class, 'swap'])->name('kelas.swap');
    Route::post('/kelas/{kelas}/acak', [DenahKelasController::class, 'acak'])->name('kelas.acak');
    Route::post('/kelas/{kelas}/tempatkan', [DenahKelasController::class, 'tempatkan'])->name('kelas.tempatkan');

    Route::get('/sekolah/data', [RuanganSekolahController::class, 'data'])->name('sekolah.data');
    Route::post('/ruangan', [RuanganSekolahController::class, 'store'])->name('ruangan.store');
    Route::put('/ruangan/{ruangan}', [RuanganSekolahController::class, 'update'])->name('ruangan.update');
    Route::delete('/ruangan/{ruangan}', [RuanganSekolahController::class, 'destroy'])->name('ruangan.destroy');
    Route::patch('/ruangan/{ruangan}/status', [RuanganSekolahController::class, 'status'])->name('ruangan.status');
});

// ---- Fitur khusus administrator (lihat routes/admin.php) ----
require __DIR__.'/admin.php';
