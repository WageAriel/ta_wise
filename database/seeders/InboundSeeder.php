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
        $barangNames = [
            'Laptop ASUS ROG',
            'Keyboard Mechanical',
            'Mouse Logitech Master',
            'Monitor Dell UltraSharp',
            'RAM Corsair 16GB',
            'SSD Samsung 1TB',
            'Motherboard MSI B550',
            'Power Supply 750W',
            'Casing PC NZXT',
            'Headset HyperX'
        ];

        foreach ($barangNames as $name) {
            if (!Barang::where('nama_barang', $name)->exists()) {
                Barang::create([
                    'nama_barang' => $name,
                    'satuan' => 'Pcs',
                    'status' => 'Aktif',
                    'min_stock' => rand(5, 15),
                    'max_stock' => rand(50, 150)
                ]);
            }
        }

        $barangs = Barang::take(10)->get();

        // Cari atau buat Purchase Order Dummy
        $po = PurchaseOrder::first();
        $poId = $po ? $po->id : null;

        // Buat Inbound 1-10
        for ($i = 1; $i <= 10; $i++) {
            $inboundId = 'INB-' . date('Ym') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            
            // Cek jika sudah ada agar tidak error duplikat
            $inbound = Inbound::where('id_inbound', $inboundId)->first();
            if (!$inbound) {
                $inbound = Inbound::create([
                    'id_inbound' => $inboundId,
                    'purchase_order_id' => $poId,
                    'tanggal' => now()->subDays(rand(1, 30))->toDateString(),
                    'status' => 'Pending',
                ]);
            }

            // Pilih beberapa barang acak untuk tiap inbound
            $numItems = rand(2, 5);
            $randomBarangs = $barangs->random($numItems);

            foreach ($randomBarangs as $barang) {
                // Cek agar tidak ada duplicate id_inbound & id_barang jika seeder di run ulang
                $exists = InboundItem::where('id_inbound', $inbound->id_inbound)
                                     ->where('id_barang', $barang->id_barang)
                                     ->exists();
                if (!$exists) {
                    InboundItem::create([
                        'id_inbound' => $inbound->id_inbound,
                        'id_barang' => $barang->id_barang,
                        'qty' => rand(10, 50)
                    ]);
                }
            }
        }
    }
}
