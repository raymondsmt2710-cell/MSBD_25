<?php

use App\Http\Controllers\Admin\AkunController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DenahController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\JabatanController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\KasController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\MapelController;
use App\Http\Controllers\Admin\OrangTuaController;
use App\Http\Controllers\Admin\OrganisasiController;
use App\Http\Controllers\Admin\PengurusController;
use App\Http\Controllers\Admin\PresensiController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\TahunAjaranController;
use App\Support\PolaRute;
use Illuminate\Support\Facades\Route;

$id = PolaRute::ID;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->where([
        'user' => $id, 'siswa' => $id, 'guru' => $id, 'orang_tua' => $id,
        'tahun_ajaran' => $id, 'mapel' => $id, 'jabatan' => $id, 'kelas' => $id,
        'anggota' => $id, 'jadwal' => $id, 'organisasi' => $id, 'pengurus' => $id,
        'sesi' => $id, 'transaksi' => $id, 'denah' => $id,
    ])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');


        Route::resource('akun', AkunController::class)
            ->parameters(['akun' => 'user'])
            ->except(['show', 'destroy']);
        Route::post('akun/{user}/status', [AkunController::class, 'ubahStatus'])->name('akun.status');
        Route::post('akun/{user}/reset-password', [AkunController::class, 'resetPassword'])->name('akun.reset');


        Route::resource('siswa', SiswaController::class)->except(['show', 'destroy']);
        Route::post('siswa/{siswa}/status', [SiswaController::class, 'ubahStatus'])->name('siswa.status');

        Route::resource('guru', GuruController::class)->except(['show', 'destroy']);

        Route::resource('orang-tua', OrangTuaController::class)
            ->parameters(['orang-tua' => 'orang_tua'])
            ->except(['destroy']);
        Route::post('orang-tua/{orang_tua}/anak', [OrangTuaController::class, 'hubungkan'])->name('orang-tua.anak.simpan');
        Route::put('orang-tua/{orang_tua}/anak/{siswa}', [OrangTuaController::class, 'ubahHubungan'])->name('orang-tua.anak.ubah');
        Route::delete('orang-tua/{orang_tua}/anak/{siswa}', [OrangTuaController::class, 'putuskan'])->name('orang-tua.anak.hapus');

        Route::resource('tahun-ajaran', TahunAjaranController::class)
            ->parameters(['tahun-ajaran' => 'tahun_ajaran'])
            ->except(['show', 'create']);
        Route::post('tahun-ajaran/{tahun_ajaran}/aktifkan', [TahunAjaranController::class, 'aktifkan'])->name('tahun-ajaran.aktifkan');

        Route::resource('mapel', MapelController::class)->except(['show', 'create']);
        Route::resource('jabatan', JabatanController::class)->except(['show', 'create']);


        Route::resource('kelas', KelasController::class)->parameters(['kelas' => 'kelas']);
        Route::post('kelas/{kelas}/anggota', [KelasController::class, 'tambahAnggota'])->name('kelas.anggota.simpan');
        Route::put('kelas/{kelas}/anggota', [KelasController::class, 'simpanNomorAbsen'])->name('kelas.anggota.absen');
        Route::post('kelas/{kelas}/anggota/urutkan', [KelasController::class, 'urutkanAbsen'])->name('kelas.anggota.urutkan');
        Route::delete('kelas/{kelas}/anggota/{anggota}', [KelasController::class, 'hapusAnggota'])->name('kelas.anggota.hapus');

        Route::resource('jadwal', JadwalController::class)->except(['show', 'create']);


        Route::resource('organisasi', OrganisasiController::class)->except(['show']);

        Route::get('pengurus', [PengurusController::class, 'index'])->name('pengurus.index');
        Route::get('pengurus/kelas/{kelas}', [PengurusController::class, 'kelas'])->name('pengurus.kelas');
        Route::post('pengurus/kelas/{kelas}', [PengurusController::class, 'simpanKelas'])->name('pengurus.kelas.simpan');
        Route::delete('pengurus/kelas/{kelas}/{pengurus}', [PengurusController::class, 'hapusKelas'])->name('pengurus.kelas.hapus');
        Route::get('pengurus/organisasi/{organisasi}', [PengurusController::class, 'organisasi'])->name('pengurus.organisasi');
        Route::post('pengurus/organisasi/{organisasi}', [PengurusController::class, 'simpanOrganisasi'])->name('pengurus.organisasi.simpan');
        Route::delete('pengurus/organisasi/{organisasi}/{pengurus}', [PengurusController::class, 'hapusOrganisasi'])->name('pengurus.organisasi.hapus');


        Route::get('presensi', [PresensiController::class, 'index'])->name('presensi.index');
        Route::get('presensi/sesi/{sesi}', [PresensiController::class, 'sesi'])->name('presensi.sesi');
        Route::get('presensi/izin', [PresensiController::class, 'izin'])->name('presensi.izin');

        Route::get('kas', [KasController::class, 'index'])->name('kas.index');
        Route::post('kas/{transaksi}/batal', [KasController::class, 'batalkan'])->name('kas.batal');

        Route::get('denah', [DenahController::class, 'index'])->name('denah.index');
        Route::get('denah/kelas/{kelas}', [DenahController::class, 'kelas'])->name('denah.kelas');
        Route::post('denah/kelas/{kelas}', [DenahController::class, 'store'])->name('denah.store');
        Route::get('denah/{denah}', [DenahController::class, 'edit'])->name('denah.edit');
        Route::put('denah/{denah}', [DenahController::class, 'simpanPosisi'])->name('denah.posisi');
        Route::post('denah/{denah}/aktifkan', [DenahController::class, 'aktifkan'])->name('denah.aktifkan');
        Route::delete('denah/{denah}', [DenahController::class, 'destroy'])->name('denah.destroy');
    });
