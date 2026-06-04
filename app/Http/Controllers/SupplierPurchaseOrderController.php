<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitSupplierVerificationRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * SupplierPurchaseOrdersController - Supplier management untuk PO yang diterima
 */
class SupplierPurchaseOrdersController extends Controller
{
    public function index(Request $request)
    {
        $segment = $request->query('segment', 'ongoing');
        $search = trim((string) $request->query('search', ''));
        $month = $request->query('month') ?? now()->month;
        $year = $request->query('year') ?? now()->year;

        $supplierId = $request->user()?->supplier?->id;

        $query = PurchaseOrder::query()
            ->with(['items.barang', 'items.itemType', 'items.subtype'])
            ->where('supplier_id', $supplierId);

        if ($segment === 'completed') {
            $query->whereIn('status', [PurchaseOrder::STATUS_COMPLETED]);
        } else {
            $query->whereIn('status', [
                PurchaseOrder::STATUS_RFQ,
                PurchaseOrder::STATUS_VERIFICATION,
                PurchaseOrder::STATUS_REQUEST,
                PurchaseOrder::STATUS_COMPLETENESS,
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_SHIPMENT,
            ]);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('items.barang', function ($barangQuery) use ($search) {
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
            ->where('supplier_id', $supplierId)
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $settings = PurchaseOrderSetting::current();

        return Inertia::render('Supplier/PurchaseOrderSupplier/PurchaseOrders', [
            'purchaseOrders' => $purchaseOrders,
            'segment' => $segment,
            'filters' => [
                'search' => $search,
                'month' => $month,
                'year' => $year,
            ],
            'years' => $years,
            'poDescription' => $settings->supplier_description ?? $settings->description,
        ]);
    }

    public function storeShipment(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->supplier_id !== auth()->user()->supplier->id) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($po->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SHIPMENT], true)) {
            abort(422, 'PO harus dalam status approved atau shipment');
        }

        $validated = $request->validate([
            'driver_name' => ['nullable', 'string', 'max:191'],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'carrier' => ['nullable', 'string', 'max:191'],
            'tracking_number' => ['nullable', 'string', 'max:191'],
            'shipment_notes' => ['nullable', 'string'],
            'weighing_note_path' => ['nullable', 'string', 'max:255'],
            'delivery_note_path' => ['nullable', 'string', 'max:255'],
        ]);

        $po->update(array_merge($validated, [
            'status' => PurchaseOrder::STATUS_SHIPMENT,
            'shipped_at' => now(),
        ]));

        return redirect('/supplier/purchase-orders?segment=ongoing')->with('success',
            "Data shipment untuk PO '{$po->po_number}' berhasil disimpan."
        );
    }
    /**
     * Supplier submit verification untuk RFQ yang diterima
     */
    public function submitVerification(SubmitSupplierVerificationRequest $request)
    {
        $validated = $request->validated();
        $po = PurchaseOrder::findOrFail($validated['po_id']);

        // Verify supplier mengakses PO milik mereka
        if ($po->supplier_id !== auth()->user()->supplier->id) {
            abort(403, 'Unauthorized');
        }

        if ($po->status !== PurchaseOrder::STATUS_RFQ) {
            abort(422, 'PO harus dalam status RFQ untuk submit verification');
        }

        DB::transaction(function () use ($po, $validated) {
            // Update PO status
            $po->update(['status' => PurchaseOrder::STATUS_VERIFICATION]);

            // Update items dengan supplier offer
            foreach ($validated['items'] as $item) {
                $po->items()->where('id', $item['purchase_order_item_id'])->update([
                    'supplier_offered_price' => $item['supplier_price'],
                    'supplier_offered_quantity' => $item['supplier_quantity'],
                ]);
            }
        });

        return redirect('/supplier/purchase-orders?segment=ongoing')->with('success',
            "Verification untuk PO '{$po->po_number}' berhasil di-submit."
        );
    }

    /**
     * Supplier update verification berdasarkan counter-offer admin
     */
    public function updateVerification($id, Request $request)
    {
        $po = PurchaseOrder::findOrFail($id);

        // Verify supplier
        if ($po->supplier_id !== auth()->user()->supplier->id) {
            abort(403, 'Unauthorized');
        }

        if ($po->status !== PurchaseOrder::STATUS_VERIFICATION) {
            abort(422, 'PO harus dalam status VERIFICATION');
        }

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.supplier_price' => ['required', 'numeric', 'min:0'],
            'items.*.supplier_quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($po, $validated) {
            foreach ($validated['items'] as $item) {
                $po->items()->where('id', $item['purchase_order_item_id'])->update([
                    'supplier_offered_price' => $item['supplier_price'],
                    'supplier_offered_quantity' => $item['supplier_quantity'],
                ]);
            }
        });

        return redirect('/supplier/purchase-orders?segment=ongoing')->with('success',
            "Verification untuk PO '{$po->po_number}' berhasil di-update."
        );
    }
}
