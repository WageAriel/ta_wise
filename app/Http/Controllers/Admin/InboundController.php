<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Layout;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\Barang;
use Inertia\Inertia;

class InboundController extends Controller
{
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
        $validated = $request->validate([
            'qty' => 'required|integer|min:1',
            'id_barang' => 'required|exists:barang,id_barang',
            'id_location' => 'required|exists:location,id_location'
        ]);

        $inventory = Inventory::create($validated);

        return response()->json([
            'message' => 'Inventory added successfully',
            'inventory' => $inventory
        ]);
    }
}
