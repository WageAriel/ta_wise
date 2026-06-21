<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Layout;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\Barang;
use App\Models\PutAway;
use App\Models\ReturnBarang;
use App\Models\Inbound;
use App\Models\InboundItem;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InboundController extends Controller
{
    /**
     * Display the Inbound index page.
     */
    public function index()
    {
        $inboundsRaw = Inbound::with(['purchaseOrder', 'items'])->get();

        $inbounds = $inboundsRaw->map(function ($inb) {
            return [
                'id' => $inb->id_inbound, // Using id_inbound as string
                'id_inbound' => $inb->id_inbound,
                'id_po' => $inb->purchaseOrder ? $inb->purchaseOrder->po_number : '-',
                'jumlah' => $inb->items->sum('qty'),
                'tgl' => $inb->tanggal,
            ];
        });

        return Inertia::render('Admin/Inbound/Index', [
            'inboundData' => $inbounds
        ]);
    }

    /**
     * Get all layouts and locations for the dropdowns.
     */
    public function getLayoutLocations()
    {
        $layouts = Layout::with('locations.inventories')->get();
        // Return dummy barang as well for now
        $barangs = Barang::all();

        return response()->json([
            'layouts' => $layouts,
            'barangs' => $barangs
        ]);
    }

    /**
     * Get inbound items by id_inbound (Dummy for now)
     */
    public function getInboundItems($id_inbound)
    {
        $inbound = Inbound::with(['purchaseOrder.items.barang', 'purchaseOrder.items.subtype', 'purchaseOrder.items.itemType'])
            ->where('id_inbound', $id_inbound)
            ->first();

        if (!$inbound) {
            return response()->json([]);
        }

        // Group PO items by barang_id, sorted by id
        $poItemsGrouped = [];
        if ($inbound->purchaseOrder) {
            $poItems = $inbound->purchaseOrder->items()->orderBy('id')->get();
            foreach ($poItems as $poItem) {
                $poItemsGrouped[$poItem->barang_id][] = $poItem;
            }
        }

        // Fetch Inbound items sorted by id_isi (creation order)
        $items = InboundItem::with('barang')->where('id_inbound', $id_inbound)->orderBy('id_isi')->get();

        // Calculate how much has already been put away for this inbound per barang
        $putAways = PutAway::with('inventory')->where('id_inbound', $id_inbound)->get();

        $putAwayQtyPerBarang = [];
        foreach ($putAways as $pa) {
            if ($pa->inventory) {
                $id_barang = $pa->inventory->id_barang;
                if (!isset($putAwayQtyPerBarang[$id_barang])) {
                    $putAwayQtyPerBarang[$id_barang] = 0;
                }
                $putAwayQtyPerBarang[$id_barang] += $pa->qty;
            }
        }

        // Calculate how much has already been returned for this inbound per barang
        $returns = ReturnBarang::with('details')->where('id_inbound', $id_inbound)->get();
        $returnQtyPerBarang = [];
        foreach ($returns as $ret) {
            foreach ($ret->details as $detail) {
                $id_barang = $detail->id_barang;
                if (!isset($returnQtyPerBarang[$id_barang])) {
                    $returnQtyPerBarang[$id_barang] = 0;
                }
                $returnQtyPerBarang[$id_barang] += $detail->qty;
            }
        }

        // Keep track of total putAway + return consumed per id_barang
        $consumedQty = [];
        $seenCounts = [];
        $formattedItems = [];

        foreach ($items as $item) {
            $id_barang = $item->id_barang;

            if (!isset($consumedQty[$id_barang])) {
                $putAwayQty = $putAwayQtyPerBarang[$id_barang] ?? 0;
                $returnQty = $returnQtyPerBarang[$id_barang] ?? 0;
                $consumedQty[$id_barang] = $putAwayQty + $returnQty;
            }

            // FIFO depletion of consumed quantity for this item
            $inboundQty = $item->qty;
            $applyConsume = min($inboundQty, $consumedQty[$id_barang]);
            $consumedQty[$id_barang] -= $applyConsume;
            $remainingQty = $inboundQty - $applyConsume;

            // Track index of id_barang we are currently seeing
            if (!isset($seenCounts[$id_barang])) {
                $seenCounts[$id_barang] = 0;
            }
            $index = $seenCounts[$id_barang];
            $seenCounts[$id_barang]++;

            if ($remainingQty > 0) {
                $namaBarang = $item->barang ? $item->barang->nama_barang : 'Unknown';

                // Fetch correct subtype name using the seen count index
                $subtypeName = null;
                if (isset($poItemsGrouped[$id_barang][$index])) {
                    $poItem = $poItemsGrouped[$id_barang][$index];
                    $subtypeName = $poItem->subtype->subtype_name ?? $poItem->itemType->type_name ?? null;
                } else {
                    // Fallback to first if index out of bounds
                    $firstPoItem = isset($poItemsGrouped[$id_barang][0]) ? $poItemsGrouped[$id_barang][0] : null;
                    if ($firstPoItem) {
                        $subtypeName = $firstPoItem->subtype->subtype_name ?? $firstPoItem->itemType->type_name ?? null;
                    }
                }

                $displayNama = $subtypeName ? "{$namaBarang} - {$subtypeName}" : $namaBarang;

                $formattedItems[] = [
                    'id_barang' => $id_barang,
                    'nama_barang' => $displayNama,
                    'qty' => $remainingQty,
                    'max_qty' => $remainingQty
                ];
            }
        }

        return response()->json($formattedItems);
    }

    /**
     * Store a new layout.
     */
    public function storeLayout(Request $request)
    {
        $validated = $request->validate([
            'nama_layout' => 'required|string|max:255'
        ]);

        $layout = Layout::create($validated);

        return redirect()->back()->with('success', 'Layout created successfully');
    }

    public function storeLocation(Request $request)
    {
        $validated = $request->validate([
            'kode_location' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
            'id_layout' => 'required|exists:layout,id_layout'
        ]);

        $location = Location::create($validated);

        return redirect()->back()->with('success', 'Location created successfully');
    }

    /**
     * Manage Layout & Location Page
     */
    public function manageLayoutLocation()
    {
        $layouts = Layout::with('locations')->get();
        return Inertia::render('Admin/Inbound/LayoutLocationManager', [
            'layouts' => $layouts
        ]);
    }

    /**
     * Update Layout
     */
    public function updateLayout(Request $request, $id)
    {
        $request->validate([
            'nama_layout' => 'required|string|max:255'
        ]);

        $layout = Layout::findOrFail($id);
        $layout->update(['nama_layout' => $request->nama_layout]);

        return redirect()->back()->with('success', 'Layout updated successfully');
    }

    public function destroyLayout($id)
    {
        $layout = Layout::withCount('locations')->findOrFail($id);

        if ($layout->locations_count > 0) {
            return redirect()->back()->with('error', 'Cannot delete layout because it has locations.');
        }

        $layout->delete();

        return redirect()->back()->with('success', 'Layout deleted successfully');
    }

    /**
     * Update Location
     */
    public function updateLocation(Request $request, $id)
    {
        $request->validate([
            'kode_location' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
            'id_layout' => 'required|exists:layout,id_layout'
        ]);

        $location = Location::findOrFail($id);
        $location->update([
            'kode_location' => $request->kode_location,
            'kapasitas' => $request->kapasitas,
            'id_layout' => $request->id_layout
        ]);

        return redirect()->back()->with('success', 'Location updated successfully');
    }

    /**
     * Delete Location
     */
    public function destroyLocation($id)
    {
        $location = Location::withCount('inventories')->findOrFail($id);

        if ($location->inventories_count > 0) {
            return redirect()->back()->with('error', 'Cannot delete location because it has inventory items.');
        }

        $location->delete();

        return redirect()->back()->with('success', 'Location deleted successfully');
    }

    /**
     * Store a new inventory record.
     * Supports: regular put-away items AND returned items (marked as lost/damaged).
     */
    public function storeInventory(Request $request)
    {
        $request->validate([
            'id_inbound' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|exists:barang,id_barang',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.max_qty' => 'sometimes|integer|min:1',
            'items.*.id_location' => 'required_unless:items.*.is_returned,true|nullable|exists:location,id_location',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->items as $item) {

                // Regular Put Away
                $location = Location::findOrFail($item['id_location']);
                $currentUsed = $location->inventories()->sum('qty');
                $proposedQty = $currentUsed + $item['qty'];

                if ($proposedQty > $location->kapasitas) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Kapasitas lokasi {$location->kode_location} tidak mencukupi. (Sisa kapasitas: " . max(0, $location->kapasitas - $currentUsed) . ")"
                    ], 422);
                }

                $inventory = Inventory::updateOrCreate(
                    [
                        'id_barang' => $item['id_barang'],
                        'id_location' => $item['id_location'],
                    ],
                    [
                        'qty' => DB::raw("qty + " . $item['qty'])
                    ]
                );

                PutAway::create([
                    'id_inbound' => $request->id_inbound,
                    'id_inventory' => $inventory->id_inventory,
                    'qty' => $item['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Proses Put Away berhasil disimpan.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}

