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

        // Karena satu modal bisa berisi banyak barang, kita loop dan simpan satu-satu
        foreach ($request->items as $item) {
            ReturnBarang::create([
                'id_inbound' => $request->id_inbound,
                'tanggal'    => now(), // Atau ambil dari input jika ada
                'id_barang'  => $item['id_barang'],
                'qty'        => $item['qty'],
                'kondisi'    => $item['kondisi'],
                'alasan'     => $item['alasan'],
                'status'     => 'Pending',
            ]);
        }

        return redirect()->back()->with('success', 'Data return berhasil disimpan!');
    }
}