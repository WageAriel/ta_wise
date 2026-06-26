<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Klasifikasi;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderSetting;
use App\Models\POItemType;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPurchaseOrdersController extends Controller
{
    public function index(Request $request)
    {
        $segment = $request->query('segment', 'order-request');
        $search = trim((string) $request->query('search', ''));
        $month = $request->query('month') ?? now()->month;
        $year = $request->query('year') ?? now()->year;
        $history = $request->boolean('history');

        $query = PurchaseOrder::query()->with(['supplier', 'items.barang', 'items.itemType', 'items.subtype']);

        if ($history && $segment === 'waiting-list') {
            $query->whereRaw('1 = 0');
        } elseif ($history && $segment === 'order-request') {
            $query->whereIn('status', [PurchaseOrder::STATUS_REJECTED, PurchaseOrder::STATUS_CANCELLED]);
        } elseif ($history && $segment === 'order-list') {
            $query->whereIn('status', [PurchaseOrder::STATUS_COMPLETED]);
        } else {
            $query->forSegment($segment);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('supplier', function ($supplierQuery) use ($search) {
                    $supplierQuery->where('nama_perusahaan', 'like', "%{$search}%");
                })->orWhereHas('items.barang', function ($barangQuery) use ($search) {
                    $barangQuery->where('nama_barang', 'like', "%{$search}%");
                });
            });
        }

        if ($month !== 'all') {
            $query->whereMonth('date', (int) $month);
        }

        if ($year !== 'all') {
            $query->whereYear('date', (int) $year);
        }

        $purchaseOrders = $query->orderByDesc('date')->paginate(10);
        $purchaseOrders->appends($request->query());
        $years = PurchaseOrder::query()
            ->selectRaw('YEAR(date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $settings = PurchaseOrderSetting::current();
        $suppliers = Supplier::where('status', 'approved')
            ->orderBy('nama_perusahaan')
            ->get(['id', 'nama_perusahaan', 'status']);

        $supplierClassMap = Klasifikasi::query()
            ->with('verifikasi')
            ->whereIn('id_supplier', $suppliers->pluck('id'))
            ->latest('tanggal')
            ->latest('id_klasifikasi')
            ->get()
            ->groupBy('id_supplier')
            ->map(fn ($group) => $group->first());

        $suppliers = $suppliers->map(function ($supplier) use ($supplierClassMap) {
            $klasifikasi = $supplierClassMap->get($supplier->id);
            $kelas = $klasifikasi?->verifikasi?->keputusan_admin
                ?? $klasifikasi?->verifikasi?->rekomendasi_sistem
                ?? '-';

            return [
                'id' => $supplier->id,
                'nama_perusahaan' => $supplier->nama_perusahaan,
                'status' => $supplier->status,
                'class_status' => $supplier->status === 'approved' ? 'Terverifikasi' : ucfirst($supplier->status),
                'class_name' => $kelas,
                'kelas' => $kelas,
            ];
        })->values();

        $itemsCatalog = Barang::orderBy('nama_barang')->get(['id_barang', 'nama_barang', 'satuan']);
        $itemTypes = POItemType::with(['subtypes', 'uomConfig'])->orderBy('sort_order')->get();

        return Inertia::render('Admin/PurchaseOrdersAdmin/Index', [
            'purchaseOrders' => $purchaseOrders,
            'segment' => $segment,
            'filters' => [
                'search' => $search,
                'month' => $month,
                'year' => $year,
                'history' => $history,
            ],
            'years' => $years,
            'poDescription' => $settings->admin_description ?? $settings->description,
            'suppliers' => $suppliers,
            'itemsCatalog' => $itemsCatalog,
            'itemTypes' => $itemTypes,
            'uomOptions' => $settings->uom_options ?: PurchaseOrderSetting::defaultUomOptions(),
        ]);
    }

    public function confirmArrival(Request $request, $id)
    {
        $po = PurchaseOrder::with('items')->findOrFail($id);

        if (!in_array($po->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SHIPMENT], true)) {
            abort(422, 'PO belum siap dikonfirmasi');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($po) {
            $po->update([
                'status' => PurchaseOrder::STATUS_COMPLETED,
                'delivered_at' => now(),
            ]);

            // Create Inbound Header
            $inboundId = 'INB-' . strtoupper(uniqid());
            \App\Models\Inbound::create([
                'id_inbound' => $inboundId,
                'purchase_order_id' => $po->id,
                'tanggal' => now()->toDateString(),
                'status' => 'Pending',
            ]);

            // Create Inbound Items based on PO Items
            foreach ($po->items as $item) {
                \App\Models\InboundItem::create([
                    'id_inbound' => $inboundId,
                    'id_barang' => $item->barang_id,
                    'id_subtype' => $item->id_subtype,
                    'qty' => $item->quantity,
                ]);
            }
        });

        return redirect('/admin/purchase-orders?segment=order-list')->with('success',
            "PO '{$po->po_number}' berhasil dikonfirmasi diterima."
        );
    }
}