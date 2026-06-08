<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Inbound;
use App\Models\InboundItem;
use App\Models\Barang;
use App\Models\PurchaseOrder;

class InboundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada setidaknya 2 barang
        if (Barang::count() < 2) {
            Barang::create(['nama_barang' => 'Laptop ASUS ROG', 'satuan' => 'Unit', 'status' => 'Aktif', 'min_stock' => 5, 'max_stock' => 50]);
            Barang::create(['nama_barang' => 'Keyboard Mechanical', 'satuan' => 'Pcs', 'status' => 'Aktif', 'min_stock' => 10, 'max_stock' => 100]);
        }

        $barang1 = Barang::first();
        $barang2 = Barang::skip(1)->first();

        // Cari atau buat Purchase Order Dummy
        $po = PurchaseOrder::first();
        if (!$po) {
            // Kita coba buat PO dummy secara sederhana jika belum ada (tanpa banyak relasi untuk simplisitas)
            $poId = null; 
        } else {
            $poId = $po->id;
        }

        // Buat Inbound 1
        $inbound1 = Inbound::create([
            'id_inbound' => 'INB-' . date('Ym') . '-001',
            'purchase_order_id' => $poId,
            'tanggal' => now()->subDays(2)->toDateString(),
            'status' => 'Pending',
        ]);

        InboundItem::create([
            'id_inbound' => $inbound1->id_inbound,
            'id_barang' => $barang1->id_barang ?? 1,
            'qty' => 50
        ]);

        InboundItem::create([
            'id_inbound' => $inbound1->id_inbound,
            'id_barang' => $barang2->id_barang ?? 2,
            'qty' => 100
        ]);

        // Buat Inbound 2
        $inbound2 = Inbound::create([
            'id_inbound' => 'INB-' . date('Ym') . '-002',
            'purchase_order_id' => $poId,
            'tanggal' => now()->subDays(1)->toDateString(),
            'status' => 'Pending',
        ]);

        InboundItem::create([
            'id_inbound' => $inbound2->id_inbound,
            'id_barang' => $barang1->id_barang ?? 1,
            'qty' => 20
        ]);
    }
}
