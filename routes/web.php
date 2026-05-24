<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HeaderSoalController;
use App\Http\Controllers\PertanyaanController;
use App\Http\Controllers\KlasifikasiController;
use App\Http\Controllers\Petugas\JadwalController;
use App\Http\Controllers\Petugas\VerifikasiController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ===================================================
    // SUPPLIER ROUTES
    // ===================================================
    Route::middleware(['role:supplier'])->prefix('supplier')->name('supplier.')->group(function () {
        // Tahap 1: Data Perusahaan
        Route::get('/data', [\App\Http\Controllers\DataSupplierController::class, 'index'])->name('data');
        Route::post('/data', [\App\Http\Controllers\DataSupplierController::class, 'store'])->name('data.store');
        
        // Tahap 2: Pengajuan Seleksi (Dilindungi Middleware)
        Route::get('/selection', fn() => Inertia::render('Supplier/Selection'))
            ->middleware('supplier.approved')
            ->name('selection');
            
        // Klasifikasi Supplier
        Route::get('/classification', fn() => Inertia::render('Supplier/Klasifikasi/KlasifikasiIndex'))->name('classification');
        Route::get('/classification/data', [KlasifikasiController::class, 'supplierIndex'])->name('classification.data');
        Route::get('/klasifikasi-form', function() {
            $pertanyaans = \App\Models\Pertanyaan::where('jenis_soal', 'klasifikasi')
                ->where('status', 'aktif')
                ->with('opsis')
                ->get();

            return Inertia::render('Supplier/Klasifikasi/KlasifikasiCreate', [
                'pertanyaans' => $pertanyaans,
                'supplier'    => null, // null saat testing tanpa login supplier
            ]);
        })->name('klasifikasi-form');
        Route::get('/classification/ajukan', [KlasifikasiController::class, 'create'])->name('classification.create');
        Route::post('/classification/ajukan', [KlasifikasiController::class, 'store'])->name('classification.store');

        Route::get('/timeline', fn() => Inertia::render('Supplier/Timeline'))->name('timeline');
        Route::get('/purchase-orders', fn() => Inertia::render('Supplier/PurchaseOrders'))->name('purchase-orders.index');
    });

    // ===================================================
    // ADMIN ROUTES
    // ===================================================
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        // Rute Utama & Manajemen Data Supplier
        Route::get('/supplier/data', [\App\Http\Controllers\DataSupplierController::class, 'adminIndex'])->name('supplier.index');
        Route::delete('/supplier/data/{id}', [\App\Http\Controllers\DataSupplierController::class, 'destroy'])->name('supplier.destroy');
        
        // Fungsionalitas Ekstra: Export & Import Excel
        Route::get('/supplier/export', [\App\Http\Controllers\DataSupplierController::class, 'export'])->name('supplier.export');
        Route::post('/supplier/import', [\App\Http\Controllers\DataSupplierController::class, 'import'])->name('supplier.import');
        // Review & Persetujuan Dokumen Supplier
        Route::get('/supplier/data/{supplier}', [\App\Http\Controllers\DataSupplierController::class, 'adminShow'])->name('supplier.show');
        Route::post('/supplier/data/{supplier}/approve', [\App\Http\Controllers\DataSupplierController::class, 'adminApprove'])->name('supplier.approve');
        Route::post('/supplier/data/{supplier}/reject', [\App\Http\Controllers\DataSupplierController::class, 'adminReject'])->name('supplier.reject');
        
        // Rute-rute admin lainnya...
        Route::get('/supplier/selection', fn() => Inertia::render('Admin/SupplierSelection'))->name('supplier.selection');

        // Klasifikasi - Admin
        Route::get('/supplier/classification', function() {
            return Inertia::render('Admin/KlasifikasiView');
        })->name('supplier.classification');
        Route::get('/supplier/classification/{klasifikasi}', [KlasifikasiController::class, 'adminShow'])->name('supplier.classification.show');
        Route::patch('/supplier/classification/{klasifikasi}/status', [KlasifikasiController::class, 'adminUpdateStatus'])->name('supplier.classification.status');

        // Manajemen Soal (Header Soal) - Admin
        Route::prefix('soal')->name('soal.')->group(function () {
            Route::resource('header', HeaderSoalController::class)->names('header');
            Route::resource('pertanyaan', PertanyaanController::class)->names('pertanyaan');
        });
        Route::get('/field-officers', [\App\Http\Controllers\Admin\FieldOfficerController::class, 'index'])->name('field-officers');
        Route::post('/field-officers/petugas', [\App\Http\Controllers\Admin\FieldOfficerController::class, 'storePetugas'])->name('field-officers.petugas.store');
        Route::post('/field-officers/jadwal', [\App\Http\Controllers\Admin\FieldOfficerController::class, 'storeJadwal'])->name('field-officers.jadwal.store');
        Route::get('/purchase-orders', fn() => Inertia::render('Admin/PurchaseOrders'))->name('purchase-orders');
        Route::get('/inbound', fn() => Inertia::render('Admin/Inbound'))->name('inbound');
        Route::get('/inventory', fn() => Inertia::render('Admin/Inventory'))->name('inventory');
        Route::get('/return-management', fn() => Inertia::render('Admin/ReturnManagement'))->name('return-management');
        Route::get('/outbound', fn() => Inertia::render('Admin/Outbound'))->name('outbound');
        Route::get('/user-management', fn() => Inertia::render('Admin/UserManagement'))->name('user-management');
    });

    // ===================================================
    // PETUGAS LAPANGAN ROUTES (placeholder)
    // ===================================================
    Route::middleware(['role:petugas_lapangan'])->prefix('petugas')->name('petugas.')->group(function () {
        Route::get('/dashboard', fn() => Inertia::render('PetugasLapangan/DashboardPetugas'))->name('dashboard');
        Route::get('/jadwal', [JadwalController::class, 'index'])->name('jadwal');
        Route::get('/verifikasi/{jadwal}', [VerifikasiController::class, 'show'])->name('verifikasi.form');
        Route::post('/verifikasi/{jadwal}', [VerifikasiController::class, 'store'])->name('verifikasi.store');
        Route::get('/classification', fn() => Inertia::render('Petugas/Classification'))->name('classification');
        Route::get('/field-officers', fn() => Inertia::render('Petugas/FieldOfficers'))->name('field-officers');
    });
});




require __DIR__.'/auth.php';
