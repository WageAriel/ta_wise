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

// ============================================================
// PERTANYAAN & SOAL — Admin
// ============================================================
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

// ============================================================
// KLASIFIKASI — Supplier
// ============================================================
Route::prefix('klasifikasi')->group(function () {
    // Ambil daftar pertanyaan untuk form pengajuan
    Route::get('/pertanyaan',               [KlasifikasiController::class, 'getPertanyaan']);
    // Submit pengajuan klasifikasi
    Route::post('/',                        [KlasifikasiController::class, 'store']);
    // Riwayat pengajuan milik supplier yang sedang login
    Route::get('/saya',                     [KlasifikasiController::class, 'supplierIndex']);
});

// ============================================================
// KLASIFIKASI — Admin
// ============================================================
Route::prefix('admin/klasifikasi')->group(function () {
    Route::get('/',                                     [KlasifikasiController::class, 'adminIndex']);
    Route::get('/{klasifikasi}',                        [KlasifikasiController::class, 'adminShow']);
    Route::patch('/{klasifikasi}/status',               [KlasifikasiController::class, 'adminUpdateStatus']);
});
