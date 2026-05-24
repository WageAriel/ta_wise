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
        Route::get('/data', [\App\Http\Controllers\DataSupplierController::class, 'index'])->name('data');
        Route::post('/data', [\App\Http\Controllers\DataSupplierController::class, 'store'])->name('data.store');
        Route::get('/selection', [\App\Http\Controllers\SeleksiController::class, 'index'])->name('selection');// Halaman Utama Seleksi (Daftar Pengajuan)
        Route::middleware('supplier.approved')->group(function () { // Fitur Seleksi yang DIBATASI (Wajib Approved)
            Route::get('/selection/create', [\App\Http\Controllers\SeleksiController::class, 'create'])->name('selection.create');
            Route::post('/selection', [\App\Http\Controllers\SeleksiController::class, 'store'])->name('selection.store');
            Route::get('/selection/{id}/download', [\App\Http\Controllers\SeleksiController::class, 'downloadPdf'])->name('selection.download');
        });
        Route::get('/classification', fn() => Inertia::render('Supplier/Classification'))->name('classification');
        Route::get('/timeline', fn() => Inertia::render('Supplier/Timeline'))->name('timeline');
        Route::get('/purchase-orders', fn() => Inertia::render('Supplier/PurchaseOrders'))->name('purchase-orders.index');
        });
    });

    // ===================================================
    // ADMIN ROUTES
    // ===================================================
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        // Route Data Supplier
        Route::get('/supplier/data', [\App\Http\Controllers\DataSupplierController::class, 'adminIndex'])->name('supplier.index');
        Route::delete('/supplier/data/{id}', [\App\Http\Controllers\DataSupplierController::class, 'destroy'])->name('supplier.destroy');
        Route::get('/supplier/export', [\App\Http\Controllers\DataSupplierController::class, 'export'])->name('supplier.export');
        Route::post('/supplier/import', [\App\Http\Controllers\DataSupplierController::class, 'import'])->name('supplier.import');
        Route::get('/supplier/data/{supplier}', [\App\Http\Controllers\DataSupplierController::class, 'adminShow'])->name('supplier.show');
        Route::post('/supplier/data/{supplier}/approve', [\App\Http\Controllers\DataSupplierController::class, 'adminApprove'])->name('supplier.approve');
        Route::post('/supplier/data/{supplier}/reject', [\App\Http\Controllers\DataSupplierController::class, 'adminReject'])->name('supplier.reject');
        
        // Route Supplier Selection (Seleksi Supplier)
        Route::prefix('supplier/selection')->name('supplier.selection')->group(function () {
        Route::get('/', [\App\Http\Controllers\SeleksiController::class, 'adminIndex']); // Menampilkan tabel
        Route::get('/export', [\App\Http\Controllers\SeleksiController::class, 'adminExport'])->name('.export');
        Route::get('/{id}', [\App\Http\Controllers\SeleksiController::class, 'adminShow'])->name('.show');
        Route::delete('/{id}', [\App\Http\Controllers\SeleksiController::class, 'adminDestroy'])->name('.destroy');
    });

        // Rute-rute admin lainnya...
        Route::get('/supplier/classification', fn() => Inertia::render('Admin/SupplierClassification'))->name('supplier.classification');
        Route::get('/field-officers', fn() => Inertia::render('Admin/FieldOfficers'))->name('field-officers');
        Route::get('/purchase-orders', fn() => Inertia::render('Admin/PurchaseOrders'))->name('purchase-orders');
        Route::get('/inbound', fn() => Inertia::render('Admin/Inbound/Index'))->name('inbound');        Route::get('/inventory', fn() => Inertia::render('Admin/Inventory'))->name('inventory');
        Route::get('/return-management', fn() => Inertia::render('Admin/ReturnManagement'))->name('return-management');
        Route::get('/outbound', fn() => Inertia::render('Admin/Outbound'))->name('outbound');
        Route::get('/user-management', fn() => Inertia::render('Admin/UserManagement'))->name('user-management');
    });

    // ===================================================
    // PETUGAS LAPANGAN ROUTES (placeholder)
    // ===================================================
    Route::middleware(['role:petugas_lapangan'])->prefix('petugas')->name('petugas.')->group(function () {
        Route::get('/classification', fn() => Inertia::render('Petugas/Classification'))->name('classification');
        Route::get('/field-officers', fn() => Inertia::render('Petugas/FieldOfficers'))->name('field-officers');
    });


require __DIR__.'/auth.php';