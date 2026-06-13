<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitCounterOfferRequest;
use App\Http\Requests\ConfirmCompletenessRequest;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

/**
 * WaitingListController - Admin management untuk negosiasi & completeness PO
 *
 * Flow (Admin side - Waiting List):
 *   VERIFICATION — Admin menunggu supplier verifikasi (read-only)
 *   REQUEST — Supplier mengubah harga/qty, admin bisa accept atau counter
 *     → Accept: REQUEST → COMPLETENESS
 *     → Counter: REQUEST → VERIFICATION (kembali ke supplier)
 *   COMPLETENESS — Admin melihat dokumen & konfirmasi → APPROVED
 */
class WaitingListController extends Controller
{
    /**
     * View verification/negotiation details
     * Bisa diakses di status VERIFICATION, REQUEST, atau COMPLETENESS
     */
    public function verificationDetails($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if (!in_array($po->status, [
            PurchaseOrder::STATUS_VERIFICATION,
            PurchaseOrder::STATUS_REQUEST,
            PurchaseOrder::STATUS_COMPLETENESS,
        ], true)) {
            abort(403, 'PO bukan dalam status yang bisa dilihat di waiting list');
        }

        $po->load(['items.barang', 'items.itemType', 'items.subtype', 'supplier']);

        return response()->json([
            'po' => $po,
            'items' => $po->items()->with('barang')->get(),
        ]);
    }

    /**
     * Admin accept supplier's offer — Setuju dengan penawaran supplier.
     * Status: REQUEST → COMPLETENESS
     *
     * Saat admin menerima, nilai transaksi PO diupdate ke harga/qty supplier.
     */
    public function acceptSupplierOffer($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== PurchaseOrder::STATUS_REQUEST) {
            abort(403, 'PO tidak dalam status request');
        }

        DB::transaction(function () use ($po) {
            // Terima penawaran supplier → update nilai transaksi utama
            $po->items()->each(function ($item) {
                $supplierPrice = $item->supplier_offered_price ?? $item->unit_price;
                $supplierQty = $item->supplier_offered_quantity ?? $item->quantity;

                $item->update([
                    'unit_price' => $supplierPrice,
                    'quantity' => $supplierQty,
                    'subtotal' => $supplierPrice * $supplierQty,
                    'final_price' => $supplierPrice,
                    'final_quantity' => $supplierQty,
                ]);
            });

            $po->recalculateTotal();
            $po->update(['status' => PurchaseOrder::STATUS_COMPLETENESS]);
        });

        return redirect('/admin/purchase-orders?segment=waiting-list')->with('success',
            "Penawaran supplier untuk PO '{$po->po_number}' diterima. Lanjut ke kelengkapan dokumen."
        );
    }

    /**
     * Admin submit counter-offer ke supplier.
     * Admin mengubah harga/qty dan mengirim kembali ke supplier untuk diverifikasi.
     * Status: REQUEST → VERIFICATION (giliran supplier)
     */
    public function submitCounterOffer($id, SubmitCounterOfferRequest $request)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== PurchaseOrder::STATUS_REQUEST) {
            abort(403, 'PO tidak dalam status request');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($po, $validated) {
            // Simpan counter offer admin
            foreach ($validated['items'] as $item) {
                $po->items()->where('id', $item['id'])->update([
                    'counter_offered_price' => $item['counter_price'],
                    'counter_offered_quantity' => $item['counter_quantity'],
                ]);
            }

            // Kembalikan ke supplier untuk verifikasi ulang
            $po->update(['status' => PurchaseOrder::STATUS_VERIFICATION]);
        });

        return redirect('/admin/purchase-orders?segment=waiting-list')->with('success',
            "Counter offer dikirim ke supplier, menunggu verifikasi ulang."
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
                'surat_permohonan' => false,
                'surat_penawaran' => false,
                'purchase_order' => false,
            ],
        ]);
    }

    /**
     * Admin confirm document completeness dan move to ORDER LIST.
     * Status: COMPLETENESS → APPROVED
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
            "Dokumen PO '{$po->po_number}' dikonfirmasi. Menunggu pengiriman dari supplier."
        );
    }
}
