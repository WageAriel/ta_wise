<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    /**
     * Phase 1: Order Request - Admin dapat membuat request sebagai draft (inquiry)
     */
    public function test_admin_can_create_order_request_as_inquiry_without_supplier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barang1 = Barang::factory()->create();

        // Create inquiry draft tanpa supplier
        $response = $this->actingAs($admin)->withoutMiddleware()->post('/admin/purchase-orders/order-request', [
            'is_draft' => true,
            'description' => 'Draft order untuk testing',
            'items' => [
                [
                    'barang_id' => $barang1->id_barang,
                    'quantity' => 5,
                    'unit_price' => 100,
                    'uom' => 'pcs',
                ],
            ],
        ]);

        $response->assertRedirect('/admin/purchase-orders?segment=order-request');

        $po = PurchaseOrder::first();
        $this->assertNotNull($po);
        $this->assertNull($po->supplier_id); // Supplier nullable untuk draft
        $this->assertSame(PurchaseOrder::STATUS_INQUIRY, $po->status);
        $this->assertEquals(500.0, (float) $po->total_price);
    }

    /**
     * Admin dapat submit order request sebagai RFQ (Request For Quotation) dengan supplier
     */
    public function test_admin_can_submit_order_request_as_rfq_with_supplier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $barang1 = Barang::factory()->create();
        $barang2 = Barang::factory()->create();

        $response = $this->actingAs($admin)->withoutMiddleware()->post('/admin/purchase-orders/order-request', [
            'supplier_id' => $supplier->id,
            'description' => 'Pengadaan item gudang',
            'is_draft' => false, // Submit langsung
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

        $response->assertRedirect('/admin/purchase-orders?segment=order-request');

        $po = PurchaseOrder::first();
        $this->assertNotNull($po);
        $this->assertSame($supplier->id, $po->supplier_id);
        $this->assertSame($admin->id, $po->user_id);
        $this->assertSame(PurchaseOrder::STATUS_RFQ, $po->status); // Submit = RFQ
        $this->assertEquals(900.0, (float) $po->total_price);

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $po->id,
            'barang_id' => $barang1->id_barang,
            'subtotal' => 500,
        ]);
    }

    /**
     * Admin tidak dapat submit tanpa supplier (harus draft)
     */
    public function test_admin_cannot_submit_rfq_without_supplier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barang = Barang::factory()->create();

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->from('/admin/purchase-orders')
            ->post('/admin/purchase-orders/order-request', [
                'is_draft' => false, // Coba submit tanpa supplier
                'description' => 'Invalid RFQ',
                'items' => [
                    [
                        'barang_id' => $barang->id_barang,
                        'quantity' => 1,
                        'unit_price' => 100,
                    ],
                ],
            ]);

        $response->assertRedirect('/admin/purchase-orders');
        $response->assertSessionHasErrors('supplier_id');
        $this->assertDatabaseCount('purchase_orders', 0);
    }

    /**
     * Order request harus punya minimal 1 item
     */
    public function test_order_request_requires_at_least_one_item(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->from('/admin/purchase-orders')
            ->post('/admin/purchase-orders/order-request', [
                'supplier_id' => $supplier->id,
                'is_draft' => false,
                'items' => [],
            ]);

        $response->assertRedirect('/admin/purchase-orders');
        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('purchase_orders', 0);
    }

    /**
     * Order request tolak duplicate barang
     */
    public function test_order_request_rejects_duplicate_barang(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->from('/admin/purchase-orders')
            ->post('/admin/purchase-orders/order-request', [
                'supplier_id' => $supplier->id,
                'is_draft' => false,
                'items' => [
                    ['barang_id' => $barang->id_barang, 'quantity' => 1, 'unit_price' => 100],
                    ['barang_id' => $barang->id_barang, 'quantity' => 2, 'unit_price' => 100],
                ],
            ]);

        $response->assertRedirect('/admin/purchase-orders');
        $response->assertSessionHasErrors('items.1.barang_id');
        $this->assertDatabaseCount('purchase_orders', 0);
    }

    /**
     * Admin dapat edit order request yang masih draft/inquiry
     */
    public function test_admin_can_edit_inquiry_order_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barang1 = Barang::factory()->create();
        $barang2 = Barang::factory()->create();

        // Create inquiry
        $po = PurchaseOrder::create([
            'po_number' => PurchaseOrder::generatePoNumber(),
            'supplier_id' => null,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_INQUIRY,
            'description' => 'Draft order',
            'total_price' => 0,
        ]);

        $po->items()->create([
            'barang_id' => $barang1->id_barang,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
        ]);

        // Edit: tambah item baru
        $response = $this->actingAs($admin)->withoutMiddleware()->put("/admin/purchase-orders/{$po->id}/order-request", [
            'description' => 'Updated draft',
            'items' => [
                [
                    'barang_id' => $barang1->id_barang,
                    'quantity' => 5,
                    'unit_price' => 100,
                ],
                [
                    'barang_id' => $barang2->id_barang,
                    'quantity' => 3,
                    'unit_price' => 50,
                ],
            ],
        ]);

        $response->assertRedirect('/admin/purchase-orders?segment=order-request');

        $po->refresh();
        $this->assertSame('Updated draft', $po->description);
        $this->assertEquals(650.0, (float) $po->total_price);
        $this->assertCount(2, $po->items);
    }

    /**
     * Admin dapat delete order request yang masih inquiry
     */
    public function test_admin_can_delete_inquiry_order_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barang = Barang::factory()->create();

        $po = PurchaseOrder::create([
            'po_number' => PurchaseOrder::generatePoNumber(),
            'supplier_id' => null,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_INQUIRY,
            'description' => 'Draft order',
            'total_price' => 0,
        ]);

        $po->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()->delete("/admin/purchase-orders/{$po->id}/order-request");

        $response->assertRedirect('/admin/purchase-orders?segment=order-request');
        $this->assertDatabaseCount('purchase_orders', 0);
    }

    /**
     * Admin tidak dapat edit RFQ request (sudah dikirim ke supplier)
     */
    public function test_admin_cannot_edit_rfq_order_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $po = PurchaseOrder::create([
            'po_number' => PurchaseOrder::generatePoNumber(),
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_RFQ, // sudah RFQ
            'description' => 'Sent to supplier',
            'total_price' => 500,
        ]);

        $po->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->from('/admin/purchase-orders')
            ->put("/admin/purchase-orders/{$po->id}/order-request", [
                'description' => 'Coba edit RFQ',
                'items' => [],
            ]);

        $response->assertForbidden();
    }
}
