<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\Location;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryController extends Controller
{
    public function index()
    {
        $inventoriesRaw = Inventory::with(['barang', 'location.layout'])->get();

        $inventories = $inventoriesRaw->map(function($inv) {
            return [
                'id' => 'INV-' . str_pad($inv->id_inventory, 3, '0', STR_PAD_LEFT),
                'real_id' => $inv->id_inventory,
                'id_barang' => $inv->id_barang,
                'name' => $inv->barang->nama_barang,
                'category' => 'Raw Materials', // Asumsi statis untuk saat ini
                'currentStock' => $inv->qty,
                'unit' => $inv->barang->satuan,
                'minStock' => $inv->barang->min_stock ?? 0,
                'maxStock' => $inv->barang->max_stock ?? 0,
                'location' => ($inv->location && $inv->location->layout) ? $inv->location->layout->nama_layout 
                                . ' - ' . $inv->location->kode_location : 'Unassigned',
            ];
        });

        $locationsRaw = Location::with(['layout', 'inventories'])->get();
        $locationsData = $locationsRaw->map(function($loc) {
            $used = $loc->inventories->sum('qty');
            $kapasitas = $loc->kapasitas ?: 1; // Prevent division by zero
            return [
                'id_location' => $loc->id_location,
                'kode_location' => $loc->kode_location,
                'layout_name' => $loc->layout->nama_layout ?? '-',
                'kapasitas' => $loc->kapasitas,
                'digunakan' => $used,
                'tersisa' => max(0, $loc->kapasitas - $used),
                'persentase' => min(100, round(($used / $kapasitas) * 100, 2))
            ];
        });

        return Inertia::render('Admin/Inventory/InventoryView', [
            'inventories' => $inventories,
            'locationsData' => $locationsData
        ]);
    }

    public function downloadPdf($id_barang)
    {
        $barang = \App\Models\Barang::with(['inventories.location.layout'])->findOrFail($id_barang);

        $pdf = Pdf::loadView('pdf.restock-request', compact('barang'));
        
        return $pdf->download('Permintaan_Restock_' . str_replace(' ', '_', $barang->nama_barang) . '.pdf');
    }
}
