<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HeaderSoalController;
use App\Http\Controllers\PertanyaanController;
use App\Http\Controllers\KlasifikasiController;
use App\Http\Controllers\Petugas\JadwalController;
use App\Http\Controllers\Petugas\VerifikasiController;
use App\Http\Controllers\Admin\ReturnController; 
use App\Http\Controllers\Admin\InboundController;
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
        Route::get('/purchase-orders', [\App\Http\Controllers\SupplierPurchaseOrdersController::class, 'index'])->name('purchase-orders.index');
        
        // Purchase Order - Supplier can submit verification and update
        Route::middleware('supplier.approved')->group(function () {
            Route::post('/purchase-orders/submit-verification', [\App\Http\Controllers\SupplierPurchaseOrdersController::class, 'submitVerification'])->name('purchase-orders.submit-verification');
            Route::put('/purchase-orders/{id}/update-verification', [\App\Http\Controllers\SupplierPurchaseOrdersController::class, 'updateVerification'])->name('purchase-orders.update-verification');
            Route::post('/purchase-orders/{id}/shipment', [\App\Http\Controllers\SupplierPurchaseOrdersController::class, 'storeShipment'])->name('purchase-orders.shipment.store');
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
        Route::get('/', [\App\Http\Controllers\SeleksiController::class, 'adminIndex'])->name('.index'); // Menampilkan tabel
        Route::get('/export', [\App\Http\Controllers\SeleksiController::class, 'adminExport'])->name('.export');
        Route::post('/import', [\App\Http\Controllers\SeleksiController::class, 'adminImport'])->name('.import');
        Route::get('/{id}', [\App\Http\Controllers\SeleksiController::class, 'adminShow'])->name('.show');
        Route::post('/{id}/status', [\App\Http\Controllers\SeleksiController::class, 'adminUpdateStatus'])->name('.update-status');
        Route::delete('/{id}', [\App\Http\Controllers\SeleksiController::class, 'adminDestroy'])->name('.destroy');
    });

        // Rute-rute admin lainnya...

        // Klasifikasi - Admin
        Route::get('/supplier/classification', function() {
            return Inertia::render('Admin/KlasifikasiView');
        })->name('supplier.classification');
        Route::get('/supplier/classification/export', [KlasifikasiController::class, 'adminExport'])->name('supplier.classification.export');
        Route::get('/supplier/classification/{klasifikasi}', [KlasifikasiController::class, 'adminShow'])->name('supplier.classification.show');
        Route::patch('/supplier/classification/{klasifikasi}/status', [KlasifikasiController::class, 'adminUpdateStatus'])->name('supplier.classification.status');

        // Manajemen Soal (Header Soal) - Admin
        Route::prefix('soal')->name('soal.')->group(function () {
            Route::resource('header', HeaderSoalController::class)->names('header');
            Route::resource('pertanyaan', PertanyaanController::class)->names('pertanyaan');
        });
        
        // Settings Routes
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/general', [\App\Http\Controllers\Admin\AppSettingController::class, 'index'])->name('general');
            Route::post('/general', [\App\Http\Controllers\Admin\AppSettingController::class, 'update'])->name('general.update');
            Route::resource('kelas', \App\Http\Controllers\Admin\KelasController::class);
        });
        Route::get('/field-officers', [\App\Http\Controllers\Admin\FieldOfficerController::class, 'index'])->name('field-officers');
        Route::post('/field-officers/petugas', [\App\Http\Controllers\Admin\FieldOfficerController::class, 'storePetugas'])->name('field-officers.petugas.store');
        Route::post('/field-officers/jadwal', [\App\Http\Controllers\Admin\FieldOfficerController::class, 'storeJadwal'])->name('field-officers.jadwal.store');
        
        // Purchase Order Routes - Admin view (index)
        Route::get('/purchase-orders', [\App\Http\Controllers\AdminPurchaseOrdersController::class, 'index'])->name('purchase-orders.index');
        
        // Order Request (Phase 1 - Create/Edit/Delete draft inquiries and RFQ)
        Route::prefix('purchase-orders')->group(function () {
            Route::post('/order-request', [\App\Http\Controllers\OrderRequestController::class, 'store'])->name('order-request.store');
            Route::put('{id}/order-request', [\App\Http\Controllers\OrderRequestController::class, 'update'])->name('order-request.update');
            Route::post('{id}/promote-request', [\App\Http\Controllers\OrderRequestController::class, 'promote'])->name('order-request.promote');
            Route::delete('{id}/order-request', [\App\Http\Controllers\OrderRequestController::class, 'destroy'])->name('order-request.destroy');
            
            // Waiting List (Phase 2 - Supplier verification & completeness check)
            Route::get('{id}/verification-details', [\App\Http\Controllers\WaitingListController::class, 'verificationDetails'])->name('verification-details');
            Route::post('{id}/approve-verification', [\App\Http\Controllers\WaitingListController::class, 'approveVerification'])->name('approve-verification');
            Route::post('{id}/counter-offer', [\App\Http\Controllers\WaitingListController::class, 'submitCounterOffer'])->name('counter-offer');
            Route::get('{id}/completeness-check', [\App\Http\Controllers\WaitingListController::class, 'completenessCheck'])->name('completeness-check');
            Route::post('{id}/confirm-completeness', [\App\Http\Controllers\WaitingListController::class, 'confirmCompleteness'])->name('confirm-completeness');

            // Shipment details are stored on purchase_orders and confirmed here
            Route::post('{id}/confirm-arrival', [\App\Http\Controllers\AdminPurchaseOrdersController::class, 'confirmArrival'])->name('purchase-orders.confirm-arrival');
        });
        
        
        
        Route::get('/inbound', fn() => Inertia::render('Admin/Inbound/Index'))->name('inbound');
        Route::get('/inbound/data', [InboundController::class, 'getLayoutLocations'])->name('inbound.data');
        Route::get('/inbound/items/{id}', [InboundController::class, 'getInboundItems'])->name('inbound.items');
        Route::post('/inbound/layout', [InboundController::class, 'storeLayout'])->name('inbound.layout.store');
        Route::post('/inbound/location', [InboundController::class, 'storeLocation'])->name('inbound.location.store');
        Route::post('/inbound/inventory', [InboundController::class, 'storeInventory'])->name('inbound.inventory.store');
        Route::get('/inventory', fn() => Inertia::render('Admin/Inventory/InventoryView'))->name('inventory');
        Route::get('/return-management', [ReturnController::class, 'index'])->name('return-management');
        Route::get('/return-management/data', [ReturnController::class, 'data'])->name('return-management.data');
        Route::post('/return-management', [ReturnController::class, 'store'])->name('return-management.store');
        Route::delete('/return-management/{id}', [ReturnController::class, 'destroy'])->name('return-management.destroy');
        Route::get('/outbound', fn() => Inertia::render('Admin/Outbound'))->name('outbound');
        Route::get('/user-management', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('user-management');
        Route::delete('/user-management/{id}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('user-management.destroy');
    });

    // ===================================================
    // PETUGAS LAPANGAN ROUTES (placeholder)
    // ===================================================
    Route::middleware(['role:petugas_lapangan'])->prefix('petugas')->name('petugas.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Petugas\DashboardController::class, 'index'])->name('dashboard.petugas');
        Route::get('/jadwal', [\App\Http\Controllers\Petugas\JadwalController::class, 'index'])->name('jadwal');
        Route::get('/verifikasi/riwayat', [\App\Http\Controllers\Petugas\RiwayatController::class, 'index'])->name('verifikasi.riwayat');
        Route::get('/laporan-kinerja', [\App\Http\Controllers\Petugas\LaporanController::class, 'index'])->name('laporan');
        Route::get('/verifikasi/{jadwal}', [\App\Http\Controllers\Petugas\VerifikasiController::class, 'show'])->name('verifikasi.form');
        Route::post('/verifikasi/{jadwal}', [\App\Http\Controllers\Petugas\VerifikasiController::class, 'store'])->name('verifikasi.store');
        Route::get('/classification', fn() => Inertia::render('Petugas/Classification'))->name('classification');
        Route::get('/field-officers', fn() => Inertia::render('Petugas/FieldOfficers'))->name('field-officers');
    });
    });

    // ===================================================
    // Manajer Gudang
    // ===================================================

    // Manajer Gudang Routes (Phase 5)
    Route::middleware(['role:manajer'])->prefix('manajer')->name('manajer.')->group(function () {
        Route::get('/dashboard', function () {
            $totalPo = \App\Models\PurchaseOrder::count();
            $activePo = \App\Models\PurchaseOrder::whereIn('status', [
                \App\Models\PurchaseOrder::STATUS_RFQ,
                \App\Models\PurchaseOrder::STATUS_VERIFICATION,
                \App\Models\PurchaseOrder::STATUS_REQUEST,
                \App\Models\PurchaseOrder::STATUS_COMPLETENESS,
                \App\Models\PurchaseOrder::STATUS_APPROVED,
                \App\Models\PurchaseOrder::STATUS_SHIPMENT,
            ])->count();
            $completedPo = \App\Models\PurchaseOrder::where('status', \App\Models\PurchaseOrder::STATUS_COMPLETED)->count();
            $totalSuppliers = \App\Models\Supplier::count();
            $totalItemTypes = \App\Models\POItemType::count();

            return Inertia::render('Manajer/Dashboard', [
                'stats' => [
                    'totalPo' => $totalPo,
                    'activePo' => $activePo,
                    'completedPo' => $completedPo,
                    'totalSuppliers' => $totalSuppliers,
                    'totalItemTypes' => $totalItemTypes,
                ],
            ]);
        })->name('dashboard');
        Route::get('/purchase-order-controller', [\App\Http\Controllers\PurchaseOrderDefault::class, 'page'])->name('purchase-order-controller.index');
        Route::put('/purchase-order-controller/settings', [\App\Http\Controllers\PurchaseOrderDefault::class, 'updateSettings'])->name('purchase-order-controller.settings.update');
        // Purchase Order (manajer control over POs)
        Route::get('/purchase-orders', [\App\Http\Controllers\PurchaseOrdersController::class, 'index'])->name('purchase-orders.index');
        Route::post('/purchase-orders', [\App\Http\Controllers\PurchaseOrdersController::class, 'store'])->name('purchase-orders.store');
        Route::prefix('purchase-order-config')->name('purchase-order-config.')->group(function () {
            // Item Type Management
            Route::get('/item-types', [\App\Http\Controllers\PurchaseOrderDefault::class, 'indexItemTypes'])->name('item-types.index');
            Route::post('/item-types', [\App\Http\Controllers\PurchaseOrderDefault::class, 'storeItemType'])->name('item-types.store');
            Route::put('/item-types/{id}', [\App\Http\Controllers\PurchaseOrderDefault::class, 'updateItemType'])->name('item-types.update');
            Route::delete('/item-types/{id}', [\App\Http\Controllers\PurchaseOrderDefault::class, 'destroyItemType'])->name('item-types.destroy');

            // Item Subtype & UoM Management (nested under item type)
            Route::get('/item-types/{itemTypeId}/subtypes', [\App\Http\Controllers\PurchaseOrderDefault::class, 'indexSubtypes'])->name('subtypes.index');
            Route::post('/item-types/{itemTypeId}/subtypes', [\App\Http\Controllers\PurchaseOrderDefault::class, 'storeSubtype'])->name('subtypes.store');
            Route::put('/item-types/{itemTypeId}/subtypes/{subtypeId}', [\App\Http\Controllers\PurchaseOrderDefault::class, 'updateSubtype'])->name('subtypes.update');
            Route::delete('/item-types/{itemTypeId}/subtypes/{subtypeId}', [\App\Http\Controllers\PurchaseOrderDefault::class, 'destroySubtype'])->name('subtypes.destroy');

            // UoM Default Configuration
            Route::post('/item-types/{itemTypeId}/uom', [\App\Http\Controllers\PurchaseOrderDefault::class, 'storeUoM'])->name('uom.store');
            Route::put('/item-types/{itemTypeId}/uom/{uomConfigId}', [\App\Http\Controllers\PurchaseOrderDefault::class, 'updateUoM'])->name('uom.update');
        });

        // Manajer - Purchase Order actions (manajer view/creation kept here)
});


require __DIR__.'/auth.php';