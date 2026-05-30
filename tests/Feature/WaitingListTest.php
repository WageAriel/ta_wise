<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Barang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaitingListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    /**
     * Phase 2: Waiting List - Admin melihat PO RFQ yang menunggu respons supplier
     */
    public function test_admin_can_view_waiting_list_segment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $waitingPo = PurchaseOrder::create([
            'po_number' => 'PO-' . now()->timestamp . '-WAIT',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_RFQ,
            'description' => 'Waiting for supplier response',
            'total_price' => 0,
        ]);

        $waitingPo->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
        ]);

        $orderListPo = PurchaseOrder::create([
            'po_number' => 'PO-' . now()->timestamp . '-APPROVED',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_APPROVED,
            'description' => 'Ready for shipment',
            'total_price' => 0,
        ]);

        $orderListPo->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 5,
            'unit_price' => 200,
            'subtotal' => 1000,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->get('/admin/purchase-orders?segment=waiting-list');

        $response->assertStatus(200);
        $response->assertInertia(fn (
            \Inertia\Testing\AssertableInertia $page
        ) => $page
            ->component('PurchaseOrders/Index')
            ->where('segment', 'waiting-list')
            ->has('purchaseOrders', 1)
            ->where('purchaseOrders.0.status', PurchaseOrder::STATUS_RFQ)
        );
    }

    public function test_admin_can_view_order_list_segment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $approvedPo = PurchaseOrder::create([
            'po_number' => 'PO-' . now()->timestamp . '-ORDER',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_APPROVED,
            'description' => 'Order list item',
            'total_price' => 1000,
        ]);

        $approvedPo->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
        ]);

        $shipmentPo = PurchaseOrder::create([
            'po_number' => 'PO-' . now()->timestamp . '-SHIP',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_SHIPMENT,
            'description' => 'In transit item',
            'total_price' => 1000,
        ]);

        $shipmentPo->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->get('/admin/purchase-orders?segment=order-list');

        $response->assertStatus(200);
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('PurchaseOrders/Index')
            ->where('segment', 'order-list')
            ->has('purchaseOrders', 2)
            ->where('purchaseOrders.0.status', PurchaseOrder::STATUS_SHIPMENT)
            ->where('purchaseOrders.1.status', PurchaseOrder::STATUS_APPROVED)
        );
    }

    /**
     * Supplier dapat submit verification untuk barang yang ditawarkan
     */
    public function test_supplier_can_submit_verification_for_rfq(): void
    {
        $supplier = Supplier::factory()->create(['status' => 'approved']);
        $admin = User::factory()->create(['role' => 'admin']);
        $barang = Barang::factory()->create();

        // Create RFQ dari admin untuk supplier ini
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-VER-01',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_RFQ,
            'total_price' => 1000,
        ]);

        $item = $po->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
        ]);

        // Supplier submit verification dengan harga/qty mereka
        $response = $this->actingAs($supplier->user)->withoutMiddleware()
            ->post('/supplier/purchase-orders/submit-verification', [
                'po_id' => $po->id,
                'items' => [
                    [
                        'purchase_order_item_id' => $item->id,
                        'supplier_price' => 95, // supplier kasih harga lebih murah
                        'supplier_quantity' => 10,
                    ],
                ],
            ]);

        $response->assertRedirect('/supplier/purchase-orders?segment=ongoing');

        $po->refresh();
        $this->assertSame(PurchaseOrder::STATUS_VERIFICATION, $po->status);

        // Item harus record supplier offer
        $item->refresh();
        $this->assertEquals(95, (float) $item->supplier_offered_price);
        $this->assertSame(10, $item->supplier_offered_quantity);
    }

    /**
     * Admin dapat view supplier verification dan decide approve atau counter-offer
     */
    public function test_admin_can_view_supplier_verification_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-VER-02',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_VERIFICATION,
            'total_price' => 1000,
        ]);

        $item = $po->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
            'supplier_offered_price' => 95,
            'supplier_offered_quantity' => 10,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->get("/admin/purchase-orders/{$po->id}/verification-details");

        $response->assertStatus(200);
        // Akan menampilkan: supplier offer vs admin request, dengan opsi approve/counter
    }

    /**
     * Admin dapat approve verification (harga/qty supplier ok)
     */
    public function test_admin_can_approve_supplier_verification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-APPRVFCN',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_VERIFICATION,
            'total_price' => 1000,
        ]);

        $po->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
            'supplier_offered_price' => 100, // sama
            'supplier_offered_quantity' => 10, // sama
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->post("/admin/purchase-orders/{$po->id}/approve-verification");

        $response->assertRedirect('/admin/purchase-orders?segment=waiting-list');

        $po->refresh();
        $this->assertSame(PurchaseOrder::STATUS_COMPLETENESS, $po->status);
    }

    /**
     * Admin dapat reject verification dan request supplier resubmit
     */
    public function test_admin_can_reject_supplier_verification_and_request_resubmit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-REJVFCN',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_VERIFICATION,
            'total_price' => 1000,
        ]);

        $po->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
            'supplier_offered_price' => 150, // terlalu mahal
            'supplier_offered_quantity' => 5, // kurang
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->post("/admin/purchase-orders/{$po->id}/counter-offer", [
                'items' => [
                    [
                        'id' => 1,
                        'counter_price' => 90,
                        'counter_quantity' => 10,
                        'reason' => 'Price terlalu tinggi, quality kurang',
                    ],
                ],
            ]);

        $response->assertRedirect('/admin/purchase-orders?segment=waiting-list');

        $po->refresh();
        // Status tetap VERIFICATION, tapi ada counter offer yang diminta
        $this->assertSame(PurchaseOrder::STATUS_VERIFICATION, $po->status);
    }

    /**
     * Supplier dapat update verification berdasarkan counter-offer admin
     */
    public function test_supplier_can_update_verification_based_on_counter_offer(): void
    {
        $supplier = Supplier::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $barang = Barang::factory()->create();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-COUNTER',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_VERIFICATION,
            'total_price' => 1000,
        ]);

        $item = $po->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
            'supplier_offered_price' => 150,
            'supplier_offered_quantity' => 5,
            'counter_offered_price' => 90,
            'counter_offered_quantity' => 10,
        ]);

        // Supplier update sesuai counter offer
        $response = $this->actingAs($supplier->user)->withoutMiddleware()
            ->put('/supplier/purchase-orders/' . $po->id . '/update-verification', [
                'items' => [
                    [
                        'purchase_order_item_id' => $item->id,
                        'supplier_price' => 90,
                        'supplier_quantity' => 10,
                    ],
                ],
            ]);

        $response->assertRedirect('/supplier/purchase-orders?segment=ongoing');

        $item->refresh();
        $this->assertEquals(90, (float) $item->supplier_offered_price);
        $this->assertSame(10, $item->supplier_offered_quantity);
    }

    /**
     * Admin dapat view dan verify dokumen pelangkap (completeness check)
     */
    public function test_admin_can_view_document_completeness(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-DOC',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_COMPLETENESS,
            'total_price' => 1000,
        ]);

        $po->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
            'supplier_offered_price' => 100,
            'supplier_offered_quantity' => 10,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->get("/admin/purchase-orders/{$po->id}/completeness-check");

        $response->assertStatus(200);
        // Should show document checklist (surat permohonan, penawaran, purchase order)
    }

    /**
     * Admin dapat confirm kelengkapan dokumen move to ORDER LIST (APPROVED)
     */
    public function test_admin_can_confirm_document_completeness_and_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $barang = Barang::factory()->create();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-DOCCONF',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'date' => now(),
            'status' => PurchaseOrder::STATUS_COMPLETENESS,
            'total_price' => 1000,
        ]);

        $po->items()->create([
            'barang_id' => $barang->id_barang,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
            'supplier_offered_price' => 100,
            'supplier_offered_quantity' => 10,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->post("/admin/purchase-orders/{$po->id}/confirm-completeness", [
                'documents_verified' => [
                    'surat_permohonan' => true,
                    'surat_penawaran' => true,
                    'purchase_order' => true,
                ],
            ]);

        $response->assertRedirect('/admin/purchase-orders?segment=order-list');

        $po->refresh();
        $this->assertSame(PurchaseOrder::STATUS_APPROVED, $po->status);
    }
}
