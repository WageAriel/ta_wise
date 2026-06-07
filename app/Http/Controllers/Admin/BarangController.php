<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Barang;
use Inertia\Inertia;

class BarangController extends Controller
{
    public function index()
    {
        $barangs = Barang::all();
        return Inertia::render('Admin/Barang/Index', [
            'barangs' => $barangs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'satuan' => 'required|string|max:50',
            'status' => 'required|string|in:Aktif,Nonaktif',
            'min_stock' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
        ]);

        $validated['min_stock'] = $validated['min_stock'] ?? 0;
        $validated['max_stock'] = $validated['max_stock'] ?? 0;

        Barang::create($validated);

        return redirect()->back()->with('message', 'Barang berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);

        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'satuan' => 'required|string|max:50',
            'status' => 'required|string|in:Aktif,Nonaktif',
            'min_stock' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
        ]);

        $validated['min_stock'] = $validated['min_stock'] ?? 0;
        $validated['max_stock'] = $validated['max_stock'] ?? 0;

        $barang->update($validated);

        return redirect()->back()->with('message', 'Barang berhasil diupdate');
    }

    public function destroy($id)
    {
        $barang = Barang::findOrFail($id);
        $barang->delete();

        return redirect()->back()->with('message', 'Barang berhasil dihapus');
    }
}
