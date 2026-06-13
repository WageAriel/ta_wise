<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Layout;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\Outbound;
use App\Models\OutboundItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OutboundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    /**
     * Test admin can view outbound list page
     */
    public function test_admin_can_view_outbound_list_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/outbound');

        $response->assertStatus(200);
    }

    /**
     * Test admin can create a new outbound and decrement inventory
     */
    public function test_admin_can_create_outbound_and_decrement_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barang = Barang::factory()->create(['nama_barang' => 'Test Coffee Beans', 'satuan' => 'kg']);
        
        $layout = Layout::create(['nama_layout' => 'Warehouse Alpha']);
        $location = Location::create([
            'kode_location' => 'A1',
            'kapasitas' => 100,
            'id_layout' => $layout->id_layout
        ]);
 
        $inventory = Inventory::create([
            'id_barang' => $barang->id_barang,
            'id_location' => $location->id_location,
            'qty' => 50
        ]);
 
        $response = $this->actingAs($admin)->post('/admin/outbound', [
            'nama_penerima' => 'Toko Kopi ABC',
            'alamat_tujuan' => 'Jl. Kopi Harum No. 10',
            'kota_tujuan' => 'Surabaya',
            'telepon_penerima' => '081234567890',
            'keterangan_tujuan' => 'Kirim siang hari',
            'nama_driver' => 'Budi',
            'plat_nomor' => 'L 1234 XY',
            'carrier' => 'Armada Sendiri',
            'no_resi' => 'OB-RESI-001',
            'tanggal_keluar' => now()->toDateString(),
            'catatan_pengiriman' => 'Hati-hati pecah belah',
            'nota_timbang' => 'www.instagram.com',
            'surat_jalan' => 'https://google.com/surat',
            'items' => [
                [
                    'id_inventory' => $inventory->id_inventory,
                    'qty' => 20
                ]
            ]
        ]);
 
        $response->assertRedirect();
        
        // Assert outbound record exists
        $outbound = Outbound::first();
        $this->assertNotNull($outbound);
        $this->assertEquals('Toko Kopi ABC', $outbound->nama_penerima);
        $this->assertEquals($admin->id, $outbound->created_by);
        $this->assertEquals('www.instagram.com', $outbound->nota_timbang_path);
        $this->assertEquals('https://google.com/surat', $outbound->surat_jalan_path);
 
        // Assert outbound items exist
        $this->assertDatabaseHas('outbound_items', [
            'id_outbound' => $outbound->id_outbound,
            'id_inventory' => $inventory->id_inventory,
            'id_barang' => $barang->id_barang,
            'id_location' => $location->id_location,
            'qty' => 20
        ]);
 
        // Assert inventory is decremented
        $inventory->refresh();
        $this->assertEquals(30, $inventory->qty);
    }

    /**
     * Test admin cannot create outbound with quantity exceeding stock
     */
    public function test_admin_cannot_create_outbound_exceeding_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barang = Barang::factory()->create(['nama_barang' => 'Test Coffee Beans', 'satuan' => 'kg']);
        
        $layout = Layout::create(['nama_layout' => 'Warehouse Alpha']);
        $location = Location::create([
            'kode_location' => 'A1',
            'kapasitas' => 100,
            'id_layout' => $layout->id_layout
        ]);

        $inventory = Inventory::create([
            'id_barang' => $barang->id_barang,
            'id_location' => $location->id_location,
            'qty' => 10
        ]);

        $response = $this->actingAs($admin)->from('/admin/outbound')->post('/admin/outbound', [
            'nama_penerima' => 'Toko Kopi ABC',
            'alamat_tujuan' => 'Jl. Kopi Harum No. 10',
            'tanggal_keluar' => now()->toDateString(),
            'items' => [
                [
                    'id_inventory' => $inventory->id_inventory,
                    'qty' => 15 // Exceeds available stock
                ]
            ]
        ]);

        $response->assertRedirect('/admin/outbound');
        $response->assertSessionHasErrors('error');
        
        // Assert inventory stock is not changed
        $inventory->refresh();
        $this->assertEquals(10, $inventory->qty);
        
        // Assert no outbound was created
        $this->assertDatabaseCount('outbounds', 0);
    }

    /**
     * Test inventory is deleted when qty reaches 0
     */
    public function test_inventory_is_deleted_when_qty_reaches_zero(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barang = Barang::factory()->create();
        $layout = Layout::create(['nama_layout' => 'Warehouse Alpha']);
        $location = Location::create([
            'kode_location' => 'A1',
            'kapasitas' => 100,
            'id_layout' => $layout->id_layout
        ]);

        $inventory = Inventory::create([
            'id_barang' => $barang->id_barang,
            'id_location' => $location->id_location,
            'qty' => 10
        ]);

        $response = $this->actingAs($admin)->post('/admin/outbound', [
            'nama_penerima' => 'Toko Kopi ABC',
            'alamat_tujuan' => 'Jl. Kopi Harum No. 10',
            'tanggal_keluar' => now()->toDateString(),
            'items' => [
                [
                    'id_inventory' => $inventory->id_inventory,
                    'qty' => 10 // Takes all stock
                ]
            ]
        ]);

        $response->assertRedirect();
        
        // Assert inventory row is soft/hard deleted (controller calls delete())
        $this->assertNull(Inventory::find($inventory->id_inventory));
    }

    /**
     * Test admin can delete outbound
     */
    public function test_admin_can_delete_outbound(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $outbound = Outbound::create([
            'no_outbound' => 'OB-2026-0001',
            'nama_penerima' => 'Toko Kopi ABC',
            'alamat_tujuan' => 'Jl. Kopi Harum No. 10',
            'tanggal_keluar' => now()->toDateString(),
            'created_by' => $admin->id
        ]);

        $response = $this->actingAs($admin)->delete("/admin/outbound/{$outbound->id_outbound}");

        $response->assertRedirect();
        $this->assertDatabaseCount('outbounds', 0);
    }
}
