<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KlasifikasiController;
use App\Http\Controllers\HeaderSoalController;
use App\Http\Controllers\PertanyaanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    // ============================================================
    // PERTANYAAN & SOAL — Admin
    // ============================================================
    Route::middleware('role:admin')->group(function () {
        Route::prefix('pertanyaan')->group(function () {
            Route::get('/',                         [PertanyaanController::class, 'index']);
            Route::post('/',                        [PertanyaanController::class, 'store']);
            Route::get('/{pertanyaan}',             [PertanyaanController::class, 'show']);
            Route::put('/{pertanyaan}',             [PertanyaanController::class, 'update']);
            Route::delete('/{pertanyaan}',          [PertanyaanController::class, 'destroy']);
        });

        Route::prefix('soal/header')->group(function () {
            Route::get('/',                         [HeaderSoalController::class, 'index']);
            Route::post('/',                        [HeaderSoalController::class, 'store']);
            Route::get('/{headerSoal}',             [HeaderSoalController::class, 'show']);
            Route::put('/{headerSoal}',             [HeaderSoalController::class, 'update']);
            Route::delete('/{headerSoal}',          [HeaderSoalController::class, 'destroy']);
        });
    });

    // ============================================================
    // KLASIFIKASI — Supplier
    // ============================================================
    Route::middleware('role:supplier')->prefix('klasifikasi')->group(function () {
        // Ambil daftar pertanyaan untuk form pengajuan
        Route::get('/pertanyaan',               [KlasifikasiController::class, 'getPertanyaan']);
        // Submit pengajuan klasifikasi
        Route::post('/',                        [KlasifikasiController::class, 'store']);
        // Riwayat pengajuan milik supplier yang sedang login
        Route::get('/saya',                     [KlasifikasiController::class, 'supplierIndex']);
    });

    // ============================================================
    // KLASIFIKASI — Admin & Petugas Lapangan
    // ============================================================
    Route::prefix('admin/klasifikasi')->group(function () {
        // Akses detail bisa dilihat oleh Admin dan Petugas Lapangan (untuk Detail Modal)
        Route::get('/{klasifikasi}',                        [KlasifikasiController::class, 'adminShow'])
            ->middleware('role:admin,petugas_lapangan');
            
        // Sisanya hanya untuk Admin
        Route::middleware('role:admin')->group(function () {
            Route::get('/',                                     [KlasifikasiController::class, 'adminIndex']);
            Route::patch('/{klasifikasi}/status',               [KlasifikasiController::class, 'adminUpdateStatus']);
            Route::post('/{klasifikasi}/validasi',              [KlasifikasiController::class, 'adminValidasi']);
        });
    });
});
