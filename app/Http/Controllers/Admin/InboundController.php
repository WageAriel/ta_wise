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
        // Fetch Inbound items
        $items = InboundItem::with(['barang', 'subtype'])->where('id_inbound', $id_inbound)->orderBy('id_isi')->get();

        if ($items->isEmpty()) {
            return response()->json([]);
        }

        // Calculate how much has already been put away for this inbound per item
        $putAways = PutAway::with('inventory')->where('id_inbound', $id_inbound)->get();

        $putAwayQtyPerItem = [];
        foreach ($putAways as $pa) {
            if ($pa->inventory) {
                $key = $pa->inventory->id_barang . '_' . ($pa->inventory->id_subtype ?? '0');
                if (!isset($putAwayQtyPerItem[$key])) {
                    $putAwayQtyPerItem[$key] = 0;
                }
                $putAwayQtyPerItem[$key] += $pa->qty;
            }
        }

        // Calculate how much has already been returned for this inbound
        $returns = ReturnBarang::with('details')->where('id_inbound', $id_inbound)->get();
        $returnQtyPerItem = [];
        foreach ($returns as $ret) {
            foreach ($ret->details as $detail) {
                $key = $detail->id_barang . '_' . ($detail->id_subtype ?? '0');
                if (!isset($returnQtyPerItem[$key])) {
                    $returnQtyPerItem[$key] = 0;
                }
                $returnQtyPerItem[$key] += $detail->qty;
            }
        }

        $consumedQty = [];
        $formattedItems = [];

        foreach ($items as $item) {
            $id_barang = $item->id_barang;
            $id_subtype = $item->id_subtype;
            $key = $id_barang . '_' . ($id_subtype ?? '0');

            if (!isset($consumedQty[$key])) {
                $putAwayQty = $putAwayQtyPerItem[$key] ?? 0;
                $returnQty = $returnQtyPerItem[$key] ?? 0;
                $consumedQty[$key] = $putAwayQty + $returnQty;
            }

            // FIFO depletion
            $inboundQty = $item->qty;
            $applyConsume = min($inboundQty, $consumedQty[$key]);
            $consumedQty[$key] -= $applyConsume;
            $remainingQty = $inboundQty - $applyConsume;

            if ($remainingQty > 0) {
                $namaBarang = $item->barang ? $item->barang->nama_barang : 'Unknown';
                $subtypeName = $item->subtype ? $item->subtype->subtype_name : null;

                $displayNama = $subtypeName ? "{$namaBarang} - {$subtypeName}" : $namaBarang;

                $formattedItems[] = [
                    'id_isi' => $item->id_isi,
                    'id_barang' => $id_barang,
                    'id_subtype' => $id_subtype,
                    'nama_barang' => $displayNama,
                    'qty' => $remainingQty,
                    'max_qty' => $remainingQty
                ];
            }
        }

        return response()->json($formattedItems);
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
            'items.*.id_subtype' => 'nullable|exists:po_item_subtypes,id_subtype',
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
                        'id_subtype' => $item['id_subtype'] ?? null,
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

