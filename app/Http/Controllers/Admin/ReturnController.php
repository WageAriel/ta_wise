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
        $inboundsList = \App\Models\Inbound::select('id_inbound')->get();

        return Inertia::render('Admin/Return Management/Index', [
            'returns' => $returns,
            'inboundsList' => $inboundsList
        ]);
    }

    private function getGroupedReturns()
    {
        $returns = ReturnBarang::with('barang')->latest()->get();
        // Group by inbound and the exact datetime it was created
        $grouped = $returns->groupBy(function($item) {
            return $item->id_inbound . '_' . $item->created_at->format('Y-m-d H:i:s');
        });

        $result = [];
        foreach ($grouped as $group) {
            $first = $group->first();
            $result[] = [
                'id_return' => $first->id_return,
                'id_inbound' => $first->id_inbound,
                'tanggal_return' => \Carbon\Carbon::parse($first->tanggal)->format('d-M-Y'),
                'jumlah_item' => $group->count(),
                'notes' => $first->alasan,
                'items' => $group->map(function($item) {
                    return [
                        'id_return' => $item->id_return,
                        'nama_barang' => $item->barang ? $item->barang->nama_barang : '-',
                        'qty' => $item->qty,
                        'kondisi' => $item->kondisi,
                        'alasan' => $item->alasan,
                    ];
                })
            ];
        }

        return array_values($result);
    }

    public function data()
    {
        return response()->json($this->getGroupedReturns());
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

        $timestamp = now();
        foreach ($request->items as $item) {
            ReturnBarang::create([
                'id_inbound' => $request->id_inbound,
                'tanggal'    => $timestamp,
                'id_barang'  => $item['id_barang'],
                'qty'        => $item['qty'],
                'kondisi'    => $item['kondisi'],
                'alasan'     => $item['alasan'],
                'status'     => 'Success',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        return response()->json(['message' => 'Data return berhasil disimpan!']);
    }

    public function destroy($id)
    {
        $return = ReturnBarang::findOrFail($id);
        
        $siblings = ReturnBarang::where('id_inbound', $return->id_inbound)
                                ->where('created_at', $return->created_at)
                                ->get();
                                
        foreach ($siblings as $sibling) {
            $sibling->delete();
        }

        return response()->json(['message' => 'Data return berhasil dihapus!']);
    }

    public function downloadPdf($id)
    {
        $return = ReturnBarang::findOrFail($id);
        
        $siblings = ReturnBarang::with(['barang'])
                                ->where('id_inbound', $return->id_inbound)
                                ->where('created_at', $return->created_at)
                                ->get();

        $inbound = \App\Models\Inbound::with('purchaseOrder.supplier')->where('id_inbound', $return->id_inbound)->first();
        
        $supplierName = $inbound && $inbound->purchaseOrder && $inbound->purchaseOrder->supplier 
            ? $inbound->purchaseOrder->supplier->nama_perusahaan 
            : 'Unknown Supplier';
            
        $inboundDate = $inbound ? \Carbon\Carbon::parse($inbound->tanggal)->format('d-M-Y') : '-';
        $returnDate = \Carbon\Carbon::parse($return->tanggal)->format('d-M-Y');

        $data = [
            'id_inbound' => $return->id_inbound,
            'id_return' => $return->id_return,
            'supplier_name' => $supplierName,
            'inbound_date' => $inboundDate,
            'return_date' => $returnDate,
            'items' => $siblings
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.return', $data);
        return $pdf->download('Return-INB-' . $return->id_inbound . '.pdf');
    }
}