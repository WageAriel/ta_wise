<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outbound;
use App\Models\OutboundItem;
use App\Models\Inventory;
use App\Models\Layout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OutboundController extends Controller
{
    /**
     * Display outbound list page with inventory data for the form.
     */
    public function index()
    {
        $outbounds = Outbound::with(['items.barang', 'items.location'])
            ->latest()
            ->get()
            ->map(function ($ob) {
                return [
                    'id_outbound'    => $ob->id_outbound,
                    'no_outbound'    => $ob->no_outbound,
                    'nama_penerima'  => $ob->nama_penerima,
                    'alamat_tujuan'  => $ob->alamat_tujuan,
                    'kota_tujuan'    => $ob->kota_tujuan,
                    'tanggal_keluar' => $ob->tanggal_keluar?->toDateString(),
                    'carrier'        => $ob->carrier,
                    'nama_driver'    => $ob->nama_driver,
                    'plat_nomor'     => $ob->plat_nomor,
                    'no_resi'        => $ob->no_resi,
                    'catatan_pengiriman' => $ob->catatan_pengiriman,
                    'nota_timbang_path'  => $ob->nota_timbang_path,
                    'surat_jalan_path'   => $ob->surat_jalan_path,
                    'total_items'    => $ob->items->sum('qty'),
                    'items'          => $ob->items->map(fn($item) => [
                        'nama_barang' => $item->barang?->nama_barang ?? '-',
                        'lokasi'      => $item->location?->kode_location ?? '-',
                        'qty'         => $item->qty,
                    ]),
                    'created_at'     => $ob->created_at,
                ];
            });

        // Inventory with stock > 0 for the picker
        $inventoryStock = Inventory::with(['barang', 'subtype', 'location.layout'])
            ->where('qty', '>', 0)
            ->get()
            ->map(function($inv) {
                $category = $inv->barang?->nama_barang ?? '-';
                $name = $inv->subtype ? $inv->subtype->subtype_name : $category;
                
                return [
                    'id_inventory' => $inv->id_inventory,
                    'id_barang'    => $inv->id_barang,
                    'id_subtype'   => $inv->id_subtype,
                    'id_location'  => $inv->id_location,
                    'nama_barang'  => $name,
                    'satuan'       => $inv->barang?->satuan ?? 'Unit',
                    'kode_location' => $inv->location?->kode_location ?? '-',
                    'nama_layout'  => $inv->location?->layout?->nama_layout ?? '-',
                    'qty'          => $inv->qty,
                ];
            });

        return Inertia::render('Admin/Outbound', [
            'outbounds'     => $outbounds,
            'inventoryStock' => $inventoryStock,
        ]);
    }

    /**
     * Store a new outbound record, deducting inventory.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_penerima'   => 'required|string|max:255',
            'alamat_tujuan'   => 'required|string|max:500',
            'kota_tujuan'     => 'nullable|string|max:100',
            'telepon_penerima' => 'nullable|string|max:20',
            'keterangan_tujuan' => 'nullable|string',
            'nama_driver'     => 'nullable|string|max:255',
            'plat_nomor'      => 'nullable|string|max:20',
            'carrier'         => 'nullable|string|max:255',
            'no_resi'         => 'nullable|string|max:255',
            'tanggal_keluar'  => 'required|date',
            'catatan_pengiriman' => 'nullable|string',
            'nota_timbang'    => 'nullable|string|max:255',
            'surat_jalan'     => 'nullable|string|max:255',
            'items'           => 'required|array|min:1',
            'items.*.id_inventory' => 'required|exists:inventory,id_inventory',
            'items.*.qty'     => 'required|integer|min:1',
        ]);
 
        try {
            DB::beginTransaction();
 
            $outbound = Outbound::create([
                'no_outbound'       => Outbound::generateNumber(),
                'nama_penerima'     => $request->nama_penerima,
                'alamat_tujuan'     => $request->alamat_tujuan,
                'kota_tujuan'       => $request->kota_tujuan,
                'telepon_penerima'  => $request->telepon_penerima,
                'keterangan_tujuan' => $request->keterangan_tujuan,
                'nama_driver'       => $request->nama_driver,
                'plat_nomor'        => $request->plat_nomor,
                'carrier'           => $request->carrier,
                'no_resi'           => $request->no_resi,
                'tanggal_keluar'    => $request->tanggal_keluar,
                'catatan_pengiriman' => $request->catatan_pengiriman,
                'nota_timbang_path' => $request->nota_timbang,
                'surat_jalan_path'  => $request->surat_jalan,
                'created_by'        => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $inventory = Inventory::findOrFail($item['id_inventory']);

                if ($item['qty'] > $inventory->qty) {
                    throw new \Exception("Stok tidak mencukupi untuk {$inventory->barang?->nama_barang}. Tersedia: {$inventory->qty}");
                }

                OutboundItem::create([
                    'id_outbound'  => $outbound->id_outbound,
                    'id_inventory' => $inventory->id_inventory,
                    'id_barang'    => $inventory->id_barang,
                    'id_location'  => $inventory->id_location,
                    'qty'          => $item['qty'],
                ]);

                // Kurangi stok inventory
                $inventory->decrement('qty', $item['qty']);

                // Hapus baris inventory jika stok 0
                if ($inventory->fresh()->qty <= 0) {
                    $inventory->delete();
                }
            }

            DB::commit();

            return back()->with('success', "Outbound {$outbound->no_outbound} berhasil disimpan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show detail of a single outbound.
     */
    public function show($id)
    {
        $ob = Outbound::with(['items.barang', 'items.location.layout', 'creator'])->findOrFail($id);
        return response()->json($ob);
    }

    /**
     * Delete an outbound record (does NOT restore inventory).
     */
    public function destroy($id)
    {
        $ob = Outbound::findOrFail($id);
        $ob->delete();
        return back()->with('success', 'Data outbound dihapus.');
    }
}
