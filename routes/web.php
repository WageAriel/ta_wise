<?php

use App\Http\Controllers\ProfileController;
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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
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
    Route::prefix('supplier')->name('supplier.')->group(function () {
        // Tahap 1: Data Perusahaan
        Route::get('/data', [\App\Http\Controllers\DataSupplierController::class, 'index'])->name('data');
        Route::post('/data', [\App\Http\Controllers\DataSupplierController::class, 'store'])->name('data.store');
        
        // Tahap 2: Pengajuan Seleksi (Dilindungi Middleware)
        Route::get('/selection', fn() => Inertia::render('Supplier/Selection'))
            ->middleware('supplier.approved')
            ->name('selection');
            
        Route::get('/classification', fn() => Inertia::render('Supplier/Classification'))->name('classification');
        Route::get('/timeline', fn() => Inertia::render('Supplier/Timeline'))->name('timeline');
        Route::get('/purchase-orders', fn() => Inertia::render('Supplier/PurchaseOrders'))->name('purchase-orders.index');
    });

    // ===================================================
    // ADMIN ROUTES
    // ===================================================
    Route::prefix('admin')->name('admin.')->group(function () {
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
        Route::get('/supplier/classification', fn() => Inertia::render('Admin/SupplierClassification'))->name('supplier.classification');
        Route::get('/field-officers', fn() => Inertia::render('Admin/FieldOfficers'))->name('field-officers');
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
    Route::get('/petugas/classification', fn() => Inertia::render('Petugas/Classification'))->name('petugas.classification');
    Route::get('/petugas/field-officers', fn() => Inertia::render('Petugas/FieldOfficers'))->name('petugas.field-officers');
});

require __DIR__.'/auth.php';
