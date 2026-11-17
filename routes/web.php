<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KelolaPemilihanController;
use App\Http\Controllers\PresensiGuruController;
use App\Http\Controllers\PresensiSiswaController;
use App\Http\Controllers\SiswaPemilihanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->to(auth()->user()->role === 'siswa'
        ? route('siswa.pemilihan.index')
        : route('kelola.pemilihan.index'));
})->name('beranda');


Route::get('/login', [AuthController::class, 'form'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses')->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ---- Fitur 1: Presensi QR sekali pakai ----
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
