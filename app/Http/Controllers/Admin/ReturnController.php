<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use \App\Models\Inbound;
use App\Models\ReturnBarang;
use App\Models\ReturnDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReturnController extends Controller
{
    public function index()
    {
        // Ambil data return beserta informasi barangnya
        $returns = ReturnBarang::with('details.barang')->latest()->get();
        $inboundsList = Inbound::select('id_inbound')->get();

        return Inertia::render('Admin/Return Management/Index', [
            'returns' => $returns,
            'inboundsList' => $inboundsList
        ]);
    }

    private function getGroupedReturns()
    {
        $returns = ReturnBarang::with('details.barang')->latest()->get();

        $result = [];
        foreach ($returns as $return) {
            $firstDetail = $return->details->first();
            $result[] = [
                'id_return' => $return->id_return,
                'id_inbound' => $return->id_inbound,
                'tanggal_return' => \Carbon\Carbon::parse($return->tanggal)->format('d-M-Y'),
                'jumlah_item' => $return->details->count(),
                'notes' => $firstDetail ? $firstDetail->alasan : '-',
                'items' => $return->details->map(function($detail) {
                    return [
                        'id_return' => $detail->id_return,
                        'nama_barang' => $detail->barang ? $detail->barang->nama_barang : '-',
                        'qty' => $detail->qty,
                        'kondisi' => $detail->kondisi,
                        'alasan' => $detail->alasan,
                    ];
                })
            ];
        }

        return $result;
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
        $returnHeader = ReturnBarang::create([
            'id_inbound' => $request->id_inbound,
            'tanggal'    => $timestamp,
            'status'     => 'Success',
        ]);

        foreach ($request->items as $item) {
            ReturnDetail::create([
                'id_return' => $returnHeader->id_return,
                'id_barang' => $item['id_barang'],
                'qty'       => $item['qty'],
                'kondisi'   => $item['kondisi'],
                'alasan'    => $item['alasan'],
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

    public function downloadPdf($id)
    {
        $return = ReturnBarang::with('details.barang')->findOrFail($id);

        $inbound = Inbound::with(['purchaseOrder.supplier', 'purchaseOrder.items.barang', 'purchaseOrder.items.subtype', 'purchaseOrder.items.itemType'])
                        ->where('id_inbound', $return->id_inbound)
                        ->first();
        
        // Build a lookup map: barang_id => subtype/type name from PO items
        $subtypeMap = [];
        if ($inbound && $inbound->purchaseOrder) {
            foreach ($inbound->purchaseOrder->items as $poItem) {
                $subtypeName = $poItem->subtype->subtype_name ?? $poItem->itemType->type_name ?? null;
                $subtypeMap[$poItem->barang_id] = $subtypeName;
            }
        }

        // Enrich each return item with the full name including subtype
        $enrichedItems = $return->details->map(function ($item) use ($subtypeMap) {
            $baseName = $item->barang ? $item->barang->nama_barang : 'Unknown';
            $subtypeName = $subtypeMap[$item->id_barang] ?? null;
            $item->display_name = $subtypeName ? "{$baseName} - {$subtypeName}" : $baseName;
            return $item;
        });

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
            'items' => $enrichedItems,
            'user_name' => auth()->user() ? auth()->user()->username : 'Petugas Gudang',
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.return', $data);
        return $pdf->download('Return-' . $return->id_inbound . '.pdf');
    }
}