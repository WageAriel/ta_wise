<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Layout;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\Barang;
use App\Models\PutAway;
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
        $layouts = Layout::with('locations')->get();
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
        $items = InboundItem::with('barang')->where('id_inbound', $id_inbound)->get();

        $formattedItems = $items->map(function ($item) {
            return [
                'id_barang' => $item->id_barang,
                'nama_barang' => $item->barang ? $item->barang->nama_barang : 'Unknown',
                'qty' => $item->qty
            ];
        });

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

        return response()->json([
            'message' => 'Layout created successfully',
            'layout' => $layout
        ]);
    }

    /**
     * Store a new location.
     */
    public function storeLocation(Request $request)
    {
        $validated = $request->validate([
            'kode_location' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
            'id_layout' => 'required|exists:layout,id_layout'
        ]);

        $location = Location::create($validated);

        return response()->json([
            'message' => 'Location created successfully',
            'location' => $location
        ]);
    }

    /**
     * Store a new inventory record.
     */
    public function storeInventory(Request $request)
    {
         // 1. Validasi Input
        $request->validate([
            'id_inbound' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|exists:barang,id_barang',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.max_qty' => 'sometimes|integer|min:1',
            'items.*.id_location' => 'required|exists:location,id_location',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->items as $item) {
                // 2. Update atau Create data di tabel Inventory (Stok di Rak)
                // Kita cari dulu apakah barang yang sama sudah ada di lokasi tersebut
                $inventory = Inventory::updateOrCreate(
                    [
                        'id_barang'   => $item['id_barang'],
                        'id_location' => $item['id_location'],
                    ],
                    [
                        // Jika sudah ada, tambahkan Qty lama dengan Qty baru
                        'qty' => DB::raw("qty + " . $item['qty']) 
                    ]
                );

                // 3. Catat histori ke tabel Put Away
                PutAway::create([
                    'id_inbound'   => $request->id_inbound,
                    'id_inventory' => $inventory->id_inventory,
                    'qty'          => $item['qty'],
                ]);

                // 4. Logika Return Otomatis
                // Return = Barang Inbound dikurangi Barang Put Away
                $inboundQty = $item['max_qty'] ?? $item['qty'];
                $putAwayQty = $item['qty'];
                $returnQty = $inboundQty - $putAwayQty;

                if ($returnQty > 0) {
                    \App\Models\ReturnBarang::create([
                        'id_inbound' => $request->id_inbound,
                        'tanggal'    => now(),
                        'id_barang'  => $item['id_barang'],
                        'qty'        => $returnQty,
                        'kondisi'    => 'Tidak Sesuai / Rusak',
                        'alasan'     => 'Kekurangan saat put away otomatis: Inbound (' . $inboundQty . ') - Put Away (' . $putAwayQty . ') = ' . $returnQty,
                        'status'     => 'Pending',
                    ]);
                }
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
