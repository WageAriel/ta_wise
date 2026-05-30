<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Inertia\Inertia;

class AdminPurchaseOrderController extends Controller
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
}
