<?php

use App\Enums\Role;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Denah\DenahController;
use App\Http\Controllers\Denah\DenahKelasController;
use App\Http\Controllers\Denah\RuanganSekolahController;
use App\Http\Controllers\Kas\ExportKasController;
use App\Http\Controllers\Kas\IuranKasController;
use App\Http\Controllers\Kas\KasController;
use App\Http\Controllers\Kas\TransaksiKasController;
use App\Http\Controllers\PresensiGuruController;
use App\Http\Controllers\PresensiSiswaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('beranda');

Route::get('/login', [AuthController::class, 'form'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses')->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $role = request()->user()->role;

        return redirect()->route($role instanceof Role ? $role->home() : Role::from($role)->home());
    })->name('dashboard');

    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])
        ->name('admin.dashboard')->middleware('role:admin');
    Route::get('/dashboard/guru', [DashboardController::class, 'guru'])
        ->name('guru.dashboard')->middleware('role:guru');
    Route::get('/dashboard/siswa', [DashboardController::class, 'siswa'])
        ->name('siswa.dashboard')->middleware('role:siswa');
    Route::get('/dashboard/orang-tua', [DashboardController::class, 'ortu'])
        ->name('ortu.dashboard')->middleware('role:orang_tua');
});

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
