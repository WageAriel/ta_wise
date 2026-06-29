<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * SupplierPurchaseOrdersController - Supplier management untuk PO yang diterima
 *
 * Flow:
 *   RFQ → [Accept Request] → VERIFICATION → [Request Verification] → COMPLETENESS / REQUEST
 *   REQUEST → (admin accepts/counters) → COMPLETENESS / VERIFICATION (loop)
 *   COMPLETENESS → (supplier uploads docs, admin confirms) → APPROVED
 *   APPROVED → [Isi Shipment] → SHIPMENT → (admin confirms arrival) → COMPLETED
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
            $query->whereIn('status', [PurchaseOrder::STATUS_COMPLETED, PurchaseOrder::STATUS_REJECTED]);
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

    /**
     * Accept Request — Supplier menerima PO dari admin.
     * Hanya menerima, tidak mengubah harga/quantity.
     * Status: RFQ → VERIFICATION
     */
    public function acceptRequest($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->supplier_id !== auth()->user()->supplier->id) {
            abort(403, 'Unauthorized');
        }

        if ($po->status !== PurchaseOrder::STATUS_RFQ) {
            abort(422, 'PO harus dalam status RFQ untuk diterima');
        }

        $po->update(['status' => PurchaseOrder::STATUS_VERIFICATION]);

        return redirect('/supplier/purchase-orders?segment=ongoing')->with('success',
            "Request PO '{$po->po_number}' berhasil diterima. Silakan lakukan verifikasi."
        );
    }

    /**
     * Decline Request — Supplier menolak PO dari admin.
     * Status: RFQ → REJECTED
     */
    public function declineRequest($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->supplier_id !== auth()->user()->supplier->id) {
            abort(403, 'Unauthorized');
        }

        if ($po->status !== PurchaseOrder::STATUS_RFQ) {
            abort(422, 'PO harus dalam status RFQ untuk ditolak');
        }

        $po->update(['status' => PurchaseOrder::STATUS_REJECTED]);

        return redirect('/supplier/purchase-orders?segment=completed')->with('success',
            "Request PO '{$po->po_number}' telah ditolak."
        );
    }

    /**
     * Request Verification — Supplier memverifikasi detail transaksi.
     * Bisa langsung accept (tanpa ubah) → COMPLETENESS
     * Atau mengubah harga/qty → REQUEST (giliran admin)
     * Status: VERIFICATION → COMPLETENESS / REQUEST
     */
    public function requestVerification($id, Request $request)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->supplier_id !== auth()->user()->supplier->id) {
            abort(403, 'Unauthorized');
        }

        if ($po->status !== PurchaseOrder::STATUS_VERIFICATION) {
            abort(422, 'PO harus dalam status VERIFICATION');
        }

        $acceptAsIs = $request->boolean('accept_as_is');

        if ($acceptAsIs) {
            // Accept tanpa perubahan → langsung ke COMPLETENESS
            DB::transaction(function () use ($po) {
                $po->items()->each(function ($item) {
                    $item->update([
                        'final_price' => $item->unit_price,
                        'final_quantity' => $item->quantity,
                    ]);
                });
                $po->update(['status' => PurchaseOrder::STATUS_COMPLETENESS]);
            });

            return redirect('/supplier/purchase-orders?segment=ongoing')->with('success',
                "PO '{$po->po_number}' berhasil diverifikasi. Menunggu kelengkapan dokumen."
            );
        }

        // Supplier mengubah harga/qty → kirim ke admin untuk review
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
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
            // Status ke REQUEST — giliran admin untuk accept/counter
            $po->update(['status' => PurchaseOrder::STATUS_REQUEST]);
        });

        return redirect('/supplier/purchase-orders?segment=ongoing')->with('success',
            "Penawaran untuk PO '{$po->po_number}' berhasil dikirim ke admin."
        );
    }

    /**
     * Store Completeness — Supplier mengupload dokumen PO.
     * Status: tetap di COMPLETENESS, admin nanti konfirmasi ke APPROVED.
     */
    public function storeCompleteness(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->supplier_id !== auth()->user()->supplier->id) {
            abort(403, 'Unauthorized');
        }

        if ($po->status !== PurchaseOrder::STATUS_COMPLETENESS) {
            abort(422, 'PO harus dalam status COMPLETENESS');
        }

        $validated = $request->validate([
            'document_path' => ['required', 'string', 'max:255'],
        ]);

        $po->update([
            'document_path' => $validated['document_path']
        ]);

        return redirect('/supplier/purchase-orders?segment=ongoing')->with('success',
            "Dokumen untuk PO '{$po->po_number}' berhasil diunggah. Menunggu konfirmasi admin."
        );
    }

    /**
     * Store Shipment — Supplier mengisi detail pengiriman.
     * Status: APPROVED → SHIPMENT
     */
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
            'delivery_type'     => ['nullable', 'string', 'in:self,courier'],
            'driver_name'       => ['nullable', 'string', 'max:191'],
            'vehicle_plate'     => ['nullable', 'string', 'max:50'],
            'phone_number'      => ['nullable', 'string', 'max:30'],
            'courier_provider'  => ['nullable', 'string', 'max:191'],
            'tracking_number'   => ['nullable', 'string', 'max:191'],
            'shipment_notes'    => ['nullable', 'string'],
            'supplementary_doc_path' => ['nullable', 'string', 'max:255'],
        ]);

        $po->update(array_merge($validated, [
            'status'     => PurchaseOrder::STATUS_SHIPMENT,
            'shipped_at' => now(),
        ]));

        return redirect('/supplier/purchase-orders?segment=ongoing')->with('success',
            "Data shipment untuk PO '{$po->po_number}' berhasil disimpan."
        );
    }

    /**
     * Download PDF Template Dokumen Pelengkap
     */
    public function downloadTemplate(Request $request, $id)
    {
        $po = PurchaseOrder::with(['items.barang', 'items.itemType', 'items.subtype', 'supplier'])->findOrFail($id);

        if ($po->supplier_id !== auth()->user()->supplier->id) {
            abort(403, 'Unauthorized');
        }

        $type = $request->query('type');
        $validTypes = ['permohonan', 'penawaran', 'order'];

        if (!in_array($type, $validTypes)) {
            abort(404, 'Tipe dokumen tidak valid');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView("supplier.pdf.{$type}", ['po' => $po]);
        
        // Atur ukuran kertas
        $pdf->setPaper('a4', 'portrait');

        $filename = ucfirst($type) . "_{$po->po_number}.pdf";

        return $pdf->download($filename);
    }
}
