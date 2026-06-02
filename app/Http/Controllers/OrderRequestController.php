<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequestRequest;
use App\Http\Requests\UpdateOrderRequestRequest;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class OrderRequestController extends Controller
{
    /**
     * Simpan order request (create new inquiry atau RFQ)
     */
    public function store(StoreOrderRequestRequest $request)
    {
        $validated = $request->validated();
        $isDraft = (bool) $validated['is_draft'];

        $po = DB::transaction(function () use ($validated, $request, $isDraft) {
            $status = $isDraft ? PurchaseOrder::STATUS_INQUIRY : PurchaseOrder::STATUS_RFQ;

            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber(),
                'supplier_id' => $validated['supplier_id'] ?? null,
                'user_id' => $request->user()->id,
                'date' => now()->toDateString(),
                'status' => $status,
                'description' => $validated['description'] ?? null,
                'total_price' => 0,
            ]);

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];

                $po->items()->create([
                    'barang_id' => $item['barang_id'],
                    'id_item_type' => $item['item_type_id'] ?? null,
                    'id_subtype' => $item['subtype_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'uom' => $item['uom'] ?? null,
                ]);
            }

            $po->recalculateTotal();

            return $po;
        });

        return redirect('/admin/purchase-orders?segment=order-request')->with('success', 
            "Order Request '{$po->po_number}' berhasil dibuat."
        );
    }

    /**
     * Update order request (hanya untuk inquiry/draft)
     */
    public function update(UpdateOrderRequestRequest $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);
        
        if (!$this->canEditOrderRequest($po)) {
            abort(403);
        }

        $validated = $request->validated();

        $po = DB::transaction(function () use ($po, $validated) {
            $po->update([
                'description' => $validated['description'] ?? $po->description,
            ]);

            // Delete old items dan rebuild
            $po->items()->delete();

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];

                $po->items()->create([
                    'barang_id' => $item['barang_id'],
                    'id_item_type' => $item['item_type_id'] ?? null,
                    'id_subtype' => $item['subtype_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'uom' => $item['uom'] ?? null,
                ]);
            }

            $po->recalculateTotal();

            return $po;
        });

        return redirect('/admin/purchase-orders?segment=order-request')->with('success',
            "Order Request '{$po->po_number}' berhasil diperbarui."
        );
    }

    /**
     * Ubah inquiry menjadi request PO/RFQ setelah supplier dipilih.
     */
    public function promote($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== PurchaseOrder::STATUS_INQUIRY) {
            abort(422, 'Hanya inquiry yang bisa diubah menjadi request PO');
        }

        if (!$po->supplier_id) {
            abort(422, 'Supplier harus dipilih sebelum mengubah inquiry menjadi request PO');
        }

        $po->update([
            'status' => PurchaseOrder::STATUS_RFQ,
        ]);

        return redirect('/admin/purchase-orders?segment=order-request')->with('success',
            "Order Request '{$po->po_number}' berhasil diubah menjadi request PO."
        );
    }

    /**
     * Delete order request (hanya untuk inquiry/draft)
     */
    public function destroy($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        
        if (!$this->canEditOrderRequest($po)) {
            abort(403);
        }

        $poNumber = $po->po_number;
        $po->items()->delete();
        $po->delete();

        return redirect('/admin/purchase-orders?segment=order-request')->with('success',
            "Order Request '{$poNumber}' berhasil dihapus."
        );
    }

    /**
     * Check if order request can be edited/deleted - hanya jika inquiry
     */
    private function canEditOrderRequest(PurchaseOrder $po): bool
    {
        return $po->status === PurchaseOrder::STATUS_INQUIRY;
    }
}
