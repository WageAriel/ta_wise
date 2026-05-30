<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveSupplierVerificationRequest;
use App\Http\Requests\SubmitCounterOfferRequest;
use App\Http\Requests\ConfirmCompletenessRequest;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

/**
 * WaitingListController - Admin management untuk supplier verification & completeness check
 */
class WaitingListController extends Controller
{
    /**
     * View verification details (status VERIFICATION)
     */
    public function verificationDetails($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== PurchaseOrder::STATUS_VERIFICATION) {
            abort(403, 'PO bukan dalam status verification');
        }

        $po->load(['items.barang', 'supplier']);

        return response()->json([
            'po' => $po,
            'items' => $po->items()->with('barang')->get(),
        ]);
    }

    /**
     * Admin approve verification (supplier offer sesuai dengan request)
     */
    public function approveVerification($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== PurchaseOrder::STATUS_VERIFICATION) {
            abort(403, 'PO tidak dalam status verification');
        }

        DB::transaction(function () use ($po) {
            // Move to completeness check
            $po->update(['status' => PurchaseOrder::STATUS_COMPLETENESS]);

            // Set final price/qty = supplier offer
            $po->items()->update([
                'final_price' => DB::raw('supplier_offered_price'),
                'final_quantity' => DB::raw('supplier_offered_quantity'),
            ]);
        });

        return redirect('/admin/purchase-orders?segment=waiting-list')->with('success',
            "Order Request '{$po->po_number}' approved dan lanjut ke document verification."
        );
    }

    /**
     * Admin submit counter-offer ke supplier
     */
    public function submitCounterOffer($id, SubmitCounterOfferRequest $request)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== PurchaseOrder::STATUS_VERIFICATION) {
            abort(403, 'PO tidak dalam status verification');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($po, $validated) {
            // Update items dengan counter offer
            foreach ($validated['items'] as $item) {
                $po->items()->where('id', $item['id'])->update([
                    'counter_offered_price' => $item['counter_price'],
                    'counter_offered_quantity' => $item['counter_quantity'],
                ]);
            }

            // PO tetap dalam status VERIFICATION, menunggu supplier resubmit
        });

        return redirect('/admin/purchase-orders?segment=waiting-list')->with('success',
            "Counter offer dikirim ke supplier, menunggu response."
        );
    }

    /**
     * View completeness check documents (status COMPLETENESS)
     */
    public function completenessCheck($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== PurchaseOrder::STATUS_COMPLETENESS) {
            abort(403, 'PO bukan dalam status completeness check');
        }

        $po->load(['items', 'supplier']);

        return response()->json([
            'po' => $po,
            'documents' => [
                'surat_permohonan' => false, // dari supplier_docs table
                'surat_penawaran' => false,
                'purchase_order' => false,
            ],
        ]);
    }

    /**
     * Admin confirm document completeness dan move to ORDER LIST
     */
    public function confirmCompleteness($id, ConfirmCompletenessRequest $request)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== PurchaseOrder::STATUS_COMPLETENESS) {
            abort(403, 'PO tidak dalam status completeness');
        }

        $validated = $request->validated();

        // Verify all documents are checked
        if (!collect($validated['documents_verified'])->every(fn($status) => $status)) {
            abort(422, 'Semua dokumen harus verified sebelum confirm');
        }

        DB::transaction(function () use ($po) {
            $po->update([
                'status' => PurchaseOrder::STATUS_APPROVED,
            ]);

            $po->items()->update(['doc_verified' => true]);
        });

        return redirect('/admin/purchase-orders?segment=order-list')->with('success',
            "Order Request '{$po->po_number}' approved dan siap di-ship oleh supplier."
        );
    }
}
