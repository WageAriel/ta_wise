<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outbound;
use App\Models\OutboundItem;
use App\Models\OutboundRecipient;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                    'id_outbound'         => $ob->id_outbound,
                    'no_outbound'         => $ob->no_outbound,
                    'recipient_id'        => $ob->recipient_id,
                    'nama_penerima'       => $ob->nama_penerima,
                    'alamat_tujuan'       => $ob->alamat_tujuan,
                    'kota_tujuan'         => $ob->kota_tujuan,
                    'telepon_penerima'    => $ob->telepon_penerima,
                    'keterangan_tujuan'   => $ob->keterangan_tujuan,
                    'tanggal_keluar'      => $ob->tanggal_keluar?->toDateString(),
                    'delivery_type'       => $ob->delivery_type,
                    'nama_driver'         => $ob->nama_driver,
                    'plat_nomor'          => $ob->plat_nomor,
                    'phone_number'        => $ob->phone_number,
                    'courier_provider'    => $ob->courier_provider,
                    'no_resi'             => $ob->no_resi,
                    'catatan_pengiriman'  => $ob->catatan_pengiriman,
                    'supplementary_doc_path' => $ob->supplementary_doc_path,
                    'total_items'         => $ob->items->sum('qty'),
                    'items'               => $ob->items->map(fn($item) => [
                        'nama_barang' => $item->barang?->nama_barang ?? '-',
                        'lokasi'      => $item->location?->kode_location ?? '-',
                        'qty'         => $item->qty,
                    ]),
                    'created_at' => $ob->created_at,
                ];
            });

        // Inventory with stock > 0 for the picker
        $inventoryStock = Inventory::with(['barang', 'subtype', 'location.layout'])
            ->where('qty', '>', 0)
            ->get()
            ->map(function ($inv) {
                $category = $inv->barang?->nama_barang ?? '-';
                $name = $inv->subtype ? $inv->subtype->subtype_name : $category;

                return [
                    'id_inventory'  => $inv->id_inventory,
                    'id_barang'     => $inv->id_barang,
                    'id_subtype'    => $inv->id_subtype,
                    'id_location'   => $inv->id_location,
                    'nama_barang'   => $name,
                    'satuan'        => $inv->barang?->satuan ?? 'Unit',
                    'kode_location' => $inv->location?->kode_location ?? '-',
                    'nama_layout'   => $inv->location?->layout?->nama_layout ?? '-',
                    'qty'           => $inv->qty,
                ];
            });

        $recipients = OutboundRecipient::orderBy('nama_penerima')->get([
            'id_recipient', 'nama_penerima', 'alamat_tujuan',
            'kota_tujuan', 'telepon_penerima', 'keterangan_tujuan',
        ]);

        return Inertia::render('Admin/Outbound', [
            'outbounds'      => $outbounds,
            'inventoryStock' => $inventoryStock,
            'recipients'     => $recipients,
        ]);
    }

    /**
     * Store a new outbound record, deducting inventory.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_penerima'          => 'required|string|max:255',
            'alamat_tujuan'          => 'required|string|max:500',
            'kota_tujuan'            => 'nullable|string|max:100',
            'telepon_penerima'       => 'nullable|string|max:20',
            'keterangan_tujuan'      => 'nullable|string',
            'tanggal_keluar'         => 'required|date',
            'delivery_type'          => 'nullable|string|in:self,courier',
            'nama_driver'            => 'nullable|string|max:255',
            'plat_nomor'             => 'nullable|string|max:20',
            'phone_number'           => 'nullable|string|max:30',
            'courier_provider'       => 'nullable|string|max:255',
            'no_resi'                => 'nullable|string|max:255',
            'catatan_pengiriman'     => 'nullable|string',
            'supplementary_doc_path' => 'nullable|string|max:500',
            'items'                  => 'required|array|min:1',
            'items.*.id_inventory'   => 'required|exists:inventory,id_inventory',
            'items.*.qty'            => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Simpan/update master data penerima jika dipilih dari master
            $recipientId = $request->recipient_id ?: null;
            if ($request->save_as_new_recipient) {
                $rec = OutboundRecipient::create([
                    'nama_penerima'     => $request->nama_penerima,
                    'alamat_tujuan'     => $request->alamat_tujuan,
                    'kota_tujuan'       => $request->kota_tujuan,
                    'telepon_penerima'  => $request->telepon_penerima,
                    'keterangan_tujuan' => $request->keterangan_tujuan,
                ]);
                $recipientId = $rec->id_recipient;
            }

            $outbound = Outbound::create([
                'no_outbound'            => Outbound::generateNumber(),
                'recipient_id'           => $recipientId,
                'nama_penerima'          => $request->nama_penerima,
                'alamat_tujuan'          => $request->alamat_tujuan,
                'kota_tujuan'            => $request->kota_tujuan,
                'telepon_penerima'       => $request->telepon_penerima,
                'keterangan_tujuan'      => $request->keterangan_tujuan,
                'tanggal_keluar'         => $request->tanggal_keluar,
                'delivery_type'          => $request->delivery_type,
                'nama_driver'            => $request->nama_driver,
                'plat_nomor'             => $request->plat_nomor,
                'phone_number'           => $request->phone_number,
                'courier_provider'       => $request->courier_provider,
                'no_resi'                => $request->no_resi,
                'catatan_pengiriman'     => $request->catatan_pengiriman,
                'supplementary_doc_path' => $request->supplementary_doc_path,
                'created_by'             => auth()->id(),
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

                $inventory->decrement('qty', $item['qty']);

                if ($inventory->fresh()->qty <= 0) {
                    $inventory->delete();
                }
            }

            DB::commit();

            return back()->with('success', "Barang Keluar {$outbound->no_outbound} berhasil disimpan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Store a new recipient to master data.
     */
    public function storeRecipient(Request $request)
    {
        $validated = $request->validate([
            'nama_penerima'    => 'required|string|max:255',
            'alamat_tujuan'    => 'required|string|max:500',
            'kota_tujuan'      => 'nullable|string|max:100',
            'telepon_penerima' => 'nullable|string|max:20',
            'keterangan_tujuan' => 'nullable|string',
        ]);

        $recipient = OutboundRecipient::create($validated);

        return back()->with('success', "Penerima '{$recipient->nama_penerima}' berhasil ditambahkan.");
    }

    /**
     * Update an existing recipient.
     */
    public function updateRecipient(Request $request, $id)
    {
        $recipient = OutboundRecipient::findOrFail($id);

        $validated = $request->validate([
            'nama_penerima'    => 'required|string|max:255',
            'alamat_tujuan'    => 'required|string|max:500',
            'kota_tujuan'      => 'nullable|string|max:100',
            'telepon_penerima' => 'nullable|string|max:20',
            'keterangan_tujuan' => 'nullable|string',
        ]);

        $recipient->update($validated);

        return back()->with('success', "Data penerima berhasil diperbarui.");
    }

    /**
     * Delete a recipient from master data.
     */
    public function destroyRecipient($id)
    {
        $recipient = OutboundRecipient::findOrFail($id);
        $recipient->delete();
        return back()->with('success', 'Data penerima berhasil dihapus.');
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
     * Delete an outbound record.
     */
    public function destroy($id)
    {
        $ob = Outbound::findOrFail($id);
        $ob->delete();
        return back()->with('success', 'Data barang keluar dihapus.');
    }
}
