<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PresensiGuruController;
use App\Http\Controllers\PresensiSiswaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

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
