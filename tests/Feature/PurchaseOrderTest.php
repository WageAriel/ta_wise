<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    public function test_admin_can_store_purchase_order_and_total_is_calculated(): void
    {
        $manajer = User::factory()->create(['role' => 'manajer']);
        $supplier = Supplier::factory()->create();
        $barang1 = Barang::factory()->create();
        $barang2 = Barang::factory()->create();

        $response = $this->actingAs($manajer)->withoutMiddleware()->post('/manajer/purchase-orders', [
            'supplier_id' => $supplier->id,
            'description' => 'Pengadaan item gudang',
            'items' => [
                [
                    'barang_id' => $barang1->id_barang,
                    'quantity' => 10,
                    'unit_price' => 50,
                    'uom' => 'pcs',
                ],
                [
                    'barang_id' => $barang2->id_barang,
                    'quantity' => 5,
                    'unit_price' => 80,
                    'uom' => 'box',
                ],
            ],
        ]);

        $response->assertRedirect('/manajer/purchase-orders');

        $po = PurchaseOrder::query()->first();

        $this->assertNotNull($po);
        $this->assertSame($manajer->id, $po->user_id);
        $this->assertSame('draft', $po->status);
        $this->assertEquals(900.0, (float) $po->total_price);
        $this->assertStringStartsWith('PO-', $po->po_number);

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $po->id,
            'barang_id' => $barang1->id_barang,
            'subtotal' => 500,
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $po->id,
            'barang_id' => $barang2->id_barang,
            'subtotal' => 400,
        ]);
    }

    public function test_can_view_purchase_orders_index(): void
    {
        $manajer = User::factory()->create(['role' => 'manajer']);
        $user = User::factory()->create();
        PurchaseOrder::factory()->count(3)->create();

        $response = $this->actingAs($user)->withoutMiddleware()->get('/admin/purchase-orders');

        $response->assertStatus(200);
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('PurchaseOrders/Index')
            ->has('purchaseOrders', 3)
        );
    }

    public function test_store_purchase_order_requires_at_least_one_item(): void
    {
        $manajer = User::factory()->create(['role' => 'manajer']);
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($manajer)->withoutMiddleware()->from('/manajer/purchase-orders')->post('/manajer/purchase-orders', [
            'supplier_id' => $supplier->id,
            'description' => 'Tidak ada item',
            'items' => [],
        ]);

        $response->assertRedirect('/manajer/purchase-orders');
        $response->assertSessionHasErrors('items');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_store_purchase_order_rejects_duplicate_barang_in_items(): void
    {
        $manajer = User::factory()->create(['role' => 'manajer']);
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $response = $this->actingAs($manajer)->withoutMiddleware()->from('/manajer/purchase-orders')->post('/manajer/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'barang_id' => $barang->id_barang,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'uom' => 'pcs',
                ],
                [
                    'barang_id' => $barang->id_barang,
                    'quantity' => 2,
                    'unit_price' => 100,
                    'uom' => 'pcs',
                ],
            ],
        ]);

        $response->assertRedirect('/manajer/purchase-orders');
        $response->assertSessionHasErrors('items.1.barang_id');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_store_purchase_order_allows_valid_status(): void
    {
        $manajer = User::factory()->create(['role' => 'manajer']);
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $response = $this->actingAs($manajer)->withoutMiddleware()->post('/manajer/purchase-orders', [
            'supplier_id' => $supplier->id,
            'status' => 'submitted',
            'items' => [
                [
                    'barang_id' => $barang->id_barang,
                    'quantity' => 2,
                    'unit_price' => 150,
                ],
            ],
        ]);

        $response->assertRedirect('/manajer/purchase-orders');

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'status' => 'submitted',
            'total_price' => 300,
        ]);
    }
}
