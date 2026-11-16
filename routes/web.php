<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route(auth()->user()->role->home()))
        ->name('dashboard');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::view('/', 'dashboard.admin')->name('dashboard');
    });

    Route::middleware('role:guru')->prefix('guru')->name('guru.')->group(function () {
        Route::view('/', 'dashboard.guru')->name('dashboard');
    });

    Route::middleware('role:siswa')->prefix('siswa')->name('siswa.')->group(function () {
        Route::view('/', 'dashboard.siswa')->name('dashboard');
    });

    Route::middleware('role:orang_tua')->prefix('orang-tua')->name('ortu.')->group(function () {
        Route::view('/', 'dashboard.ortu')->name('dashboard');
    });
});

require __DIR__.'/auth.php';