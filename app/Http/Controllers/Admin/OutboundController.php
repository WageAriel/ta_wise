<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Outbound;
use App\Models\OutboundDetail;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Inertia\Inertia;

class OutboundController extends Controller
{
    public function index()
    {
        $outbounds = Outbound::with(['user', 'details.barang.inventories.location'])->get();
        return Inertia::render('Admin/Outbound/Index', [
            'outbounds' => $outbounds
        ]);
    }

    public function create()
    {
        // Mengambil data barang dari inventory yang stoknya lebih dari 0
        $inventories = Inventory::with('barang')->where('qty', '>', 0)->get();
        return view('admin.outbound.create', compact('inventories'));
    }

    public function getItems()
    {
        $inventories = Inventory::with('barang', 'location')->where('qty', '>', 0)->get();
        return response()->json($inventories);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'tujuan' => 'required|string',
            'barang_id' => 'required|array',
            'qty' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $outbound = Outbound::create([
                'tanggal' => $request->tanggal,
                'status' => 'Selesai', // Default status jika langsung dipotong
                'tujuan' => $request->tujuan,
                'id_user' => Auth::id() ?? 1, // Pastikan id_user tersimpan
            ]);

            foreach ($request->barang_id as $key => $id_barang) {
                $qty_keluar = $request->qty[$key];

                if ($qty_keluar > 0) {
                    // Cari barang di inventory
                    $inventory = Inventory::where('id_barang', $id_barang)->first();

                    if (!$inventory || $inventory->qty < $qty_keluar) {
                        throw new \Exception("Stok tidak mencukupi untuk barang ID: " . $id_barang);
                    }

                    // Kurangi qty di inventory
                    $inventory->qty -= $qty_keluar;
                    $inventory->save();

                    // Simpan detail outbound
                    OutboundDetail::create([
                        'id_outbound' => $outbound->id_outbound,
                        'id_barang' => $id_barang,
                        'qty' => $qty_keluar,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('outbound.index')->with('success', 'Data Outbound berhasil disimpan dan stok diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $outbound = Outbound::with('details')->findOrFail($id);
            
            // Kembalikan stok inventory
            foreach ($outbound->details as $detail) {
                $inventory = Inventory::where('id_barang', $detail->id_barang)->first();
                if ($inventory) {
                    $inventory->qty += $detail->qty;
                    $inventory->save();
                }
            }

            $outbound->details()->delete();
            $outbound->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Data Outbound berhasil dihapus dan stok dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }

    public function downloadPdf($id)
    {
        $outbound = Outbound::with(['user', 'details.barang.inventories.location'])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.outbound', compact('outbound'));
        return $pdf->download('Surat-Jalan-Outbound-' . str_pad($outbound->id_outbound, 4, '0', STR_PAD_LEFT) . '.pdf');
    }
}
