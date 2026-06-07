<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnBarang;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReturnController extends Controller
{
    public function index()
    {
        // Ambil data return beserta informasi barangnya
        $returns = ReturnBarang::with('barang')->latest()->get();

        return Inertia::render('Admin/Return Management/Index', [
            'returns' => $returns
        ]);
    }

    public function data()
    {
        $returns = ReturnBarang::with('barang')->latest()->get();
        return response()->json($returns);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_inbound' => 'required',
            'items' => 'required|array',
            'items.*.id_barang' => 'required',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.kondisi' => 'required',
            'items.*.alasan' => 'required',
        ]);

        foreach ($request->items as $item) {
            ReturnBarang::create([
                'id_inbound' => $request->id_inbound,
                'tanggal'    => now(),
                'id_barang'  => $item['id_barang'],
                'qty'        => $item['qty'],
                'kondisi'    => $item['kondisi'],
                'alasan'     => $item['alasan'],
                'status'     => 'Pending',
            ]);
        }

        return response()->json(['message' => 'Data return berhasil disimpan!']);
    }

    public function destroy($id)
    {
        $return = ReturnBarang::findOrFail($id);
        $return->delete();

        return response()->json(['message' => 'Data return berhasil dihapus!']);
    }
}