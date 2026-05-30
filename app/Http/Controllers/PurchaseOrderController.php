<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $segment = request()->query('segment');

        $purchaseOrders = PurchaseOrder::with(['supplier', 'user'])
            ->forSegment($segment)
            ->latest()
            ->get();

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'segment' => $segment,
        ]);
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $validated = $request->validated();

        $po = DB::transaction(function () use ($validated, $request) {
            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber(),
                'supplier_id' => $validated['supplier_id'],
                'user_id' => $request->user()->id,
                'date' => now()->toDateString(),
                'status' => $validated['status'] ?? PurchaseOrder::STATUS_DRAFT,
                'description' => $validated['description'] ?? null,
                'total_price' => 0,
            ]);

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];

                $po->items()->create([
                    'barang_id' => $item['barang_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'uom' => $item['uom'] ?? null,
                ]);
            }

            $po->recalculateTotal();

            return $po;
        });

        return redirect('/manajer/purchase-orders')->with('success', 'Purchase Order created successfully.');
    }
}
