<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShipmentController extends Controller
{
    /**
     * Create a shipment record for a purchase order.
     */
    public function store(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        $validated = $request->validate([
            'carrier' => ['nullable', 'string', 'max:191'],
            'tracking_number' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string'],
        ]);

        $shipment = Shipment::create(array_merge($validated, [
            'purchase_order_id' => $po->id,
            'status' => 'pending',
        ]));

        return response()->json($shipment, 201);
    }

    /**
     * List shipments for a purchase order
     */
    public function index($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        $shipments = Shipment::where('purchase_order_id', $po->id)->orderBy('created_at')->get();

        return response()->json(['shipments' => $shipments], Response::HTTP_OK);
    }

    /**
     * Mark shipment as shipped.
     */
    public function markShipped($id, $shipmentId)
    {
        $shipment = Shipment::where('purchase_order_id', $id)->findOrFail($shipmentId);
        $shipment->update(['status' => 'shipped', 'shipped_at' => now()]);

        return response()->json($shipment, Response::HTTP_OK);
    }

    /**
     * Mark shipment as delivered.
     */
    public function markDelivered($id, $shipmentId)
    {
        $shipment = Shipment::where('purchase_order_id', $id)->findOrFail($shipmentId);
        $shipment->update(['status' => 'delivered', 'delivered_at' => now()]);

        return response()->json($shipment, Response::HTTP_OK);
    }

    /**
     * Cancel shipment.
     */
    public function cancel($id, $shipmentId)
    {
        $shipment = Shipment::where('purchase_order_id', $id)->findOrFail($shipmentId);
        $shipment->update(['status' => 'cancelled']);

        return response()->json($shipment, Response::HTTP_OK);
    }
}
