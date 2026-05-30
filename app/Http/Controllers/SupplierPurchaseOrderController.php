<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitSupplierVerificationRequest;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SupplierPurchaseOrderController - Supplier management untuk PO yang diterima
 */
class SupplierPurchaseOrderController extends Controller
{
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
