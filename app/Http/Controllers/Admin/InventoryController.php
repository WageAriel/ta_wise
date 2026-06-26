<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Layout;
use App\Models\Gudang;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryController extends Controller
{
    public function index()
    {
        $inventoriesRaw = Inventory::with(['barang', 'subtype', 'location.layout'])->get();

        $inventories = $inventoriesRaw->map(function($inv) {
            $category = $inv->barang->nama_barang ?? 'Unknown';
            $name = $inv->subtype ? $inv->subtype->subtype_name : $category;

            return [
                'id' => 'INV-' . str_pad($inv->id_inventory, 3, '0', STR_PAD_LEFT),
                'real_id' => $inv->id_inventory,
                'id_barang' => $inv->id_barang,
                'id_subtype' => $inv->id_subtype,
                'name' => $name,
                'category' => $category, 
                'currentStock' => $inv->qty,
                'unit' => $inv->barang->satuan ?? 'unit',
                'minStock' => $inv->barang->min_stock ?? 0,
                'maxStock' => $inv->barang->max_stock ?? 0,
                'location' => ($inv->location && $inv->location->layout) ? $inv->location->layout->nama_layout 
                                . ' - ' . $inv->location->kode_location : 'Unassigned',
            ];
        });

        $locationsRaw = Location::with(['layout.gudang', 'inventories'])->get();
        $locationsData = $locationsRaw->map(function($loc) {
            $used = $loc->inventories->sum('qty');
            $kapasitas = $loc->kapasitas ?: 1; // Prevent division by zero
            $gudangName = $loc->layout && $loc->layout->gudang ? $loc->layout->gudang->nama_gudang : '-';
            return [
                'id_location' => $loc->id_location,
                'kode_location' => $loc->kode_location,
                'layout_name' => $loc->layout->nama_layout ?? '-',
                'gudang_name' => $gudangName,
                'kapasitas' => $loc->kapasitas,
                'digunakan' => $used,
                'tersisa' => max(0, $loc->kapasitas - $used),
                'persentase' => min(100, round(($used / $kapasitas) * 100, 2))
            ];
        });

        $gudangsRaw = Gudang::with('layouts.locations')->get();
        // still passing layoutsRaw just in case, but probably better to pass gudangsRaw
        $layoutsRaw = Layout::with('locations')->get();

        return Inertia::render('Admin/Inventory/InventoryView', [
            'inventories' => $inventories,
            'locationsData' => $locationsData,
            'layoutsRaw' => $layoutsRaw,
            'gudangsRaw' => $gudangsRaw
        ]);
    }

    public function downloadPdf($id_barang)
    {
        $barang = \App\Models\Barang::with(['inventories.location.layout'])->findOrFail($id_barang);

        $pdf = Pdf::loadView('pdf.restock-request', compact('barang'));
        
        return $pdf->download('Permintaan_Restock_' . str_replace(' ', '_', $barang->nama_barang) . '.pdf');
    }

    public function storeGudang(Request $request)
    {
        $validated = $request->validate([
            'nama_gudang' => 'required|string|max:255'
        ]);

        Gudang::create($validated);

        return redirect()->back()->with('success', 'Gudang created successfully');
    }

    public function updateGudang(Request $request, $id)
    {
        $request->validate([
            'nama_gudang' => 'required|string|max:255'
        ]);

        $gudang = Gudang::findOrFail($id);
        $gudang->update(['nama_gudang' => $request->nama_gudang]);

        return redirect()->back()->with('success', 'Gudang updated successfully');
    }

    public function destroyGudang($id)
    {
        $gudang = Gudang::withCount('layouts')->findOrFail($id);

        if ($gudang->layouts_count > 0) {
            return redirect()->back()->withErrors(['error' => 'Tidak dapat menghapus Gudang karena masih memiliki layout di dalamnya.']);
        }

        $gudang->delete();

        return redirect()->back()->with('success', 'Gudang deleted successfully');
    }
    public function storeLayout(Request $request)
    {
        $validated = $request->validate([
            'id_gudang' => 'required|exists:gudang,id_gudang',
            'nama_layout' => 'required|string|max:255'
        ]);

        Layout::create($validated);

        return redirect()->back()->with('success', 'Layout created successfully');
    }

    public function storeLocation(Request $request)
    {
        $validated = $request->validate([
            'kode_location' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
            'id_layout' => 'required|exists:layout,id_layout'
        ]);

        Location::create($validated);

        return redirect()->back()->with('success', 'Location created successfully');
    }

    public function updateLayout(Request $request, $id)
    {
        $request->validate([
            'id_gudang' => 'required|exists:gudang,id_gudang',
            'nama_layout' => 'required|string|max:255'
        ]);

        $layout = Layout::findOrFail($id);
        $layout->update([
            'id_gudang' => $request->id_gudang,
            'nama_layout' => $request->nama_layout
        ]);

        return redirect()->back()->with('success', 'Layout updated successfully');
    }

    public function destroyLayout($id)
    {
        $layout = Layout::withCount('locations')->findOrFail($id);

        if ($layout->locations_count > 0) {
            return redirect()->back()->withErrors(['error' => 'Tidak dapat menghapus layout karena masih memiliki lokasi di dalamnya.']);
        }

        $layout->delete();

        return redirect()->back()->with('success', 'Layout deleted successfully');
    }

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

    public function destroyLocation($id)
    {
        $location = Location::withCount('inventories')->findOrFail($id);

        if ($location->inventories_count > 0) {
            return redirect()->back()->withErrors(['error' => 'Tidak dapat menghapus lokasi karena masih memiliki barang/inventory di dalamnya.']);
        }

        $location->delete();

        return redirect()->back()->with('success', 'Location deleted successfully');
    }
}
