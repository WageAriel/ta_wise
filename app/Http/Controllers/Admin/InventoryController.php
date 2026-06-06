<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index()
    {
        $inventoriesRaw = Inventory::with(['barang', 'location.layout'])->get();

        $inventories = $inventoriesRaw->map(function($inv) {
            return [
                'id' => 'INV-' . str_pad($inv->id_inventory, 3, '0', STR_PAD_LEFT),
                'name' => $inv->barang->nama_barang,
                'category' => 'Raw Materials', // Asumsi statis untuk saat ini
                'currentStock' => $inv->qty,
                'unit' => $inv->barang->satuan,
                'minStock' => $inv->barang->min_stock ?? 0,
                'maxStock' => $inv->barang->max_stock ?? 0,
                'location' => ($inv->location && $inv->location->layout) ? $inv->location->layout->nama_layout . ' - ' . $inv->location->kode_location : 'Unassigned',
            ];
        });

        return Inertia::render('Admin/Inventory/InventoryView', [
            'inventories' => $inventories
        ]);
    }
}
