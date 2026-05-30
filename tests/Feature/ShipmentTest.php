<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_shipment_for_purchase_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $po = PurchaseOrder::factory()->create();

        $response = $this->actingAs($admin)->withoutMiddleware()->post('/admin/purchase-orders/'. $po->id .'/shipments', [
            'carrier' => 'JNE',
            'tracking_number' => 'JNE12345',
            'notes' => 'Siap dikirim',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('shipments', [
            'purchase_order_id' => $po->id,
            'carrier' => 'JNE',
            'tracking_number' => 'JNE12345',
            'status' => 'pending',
        ]);
    }
    public function test_admin_can_list_and_update_shipment_statuses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $po = PurchaseOrder::factory()->create();

        // create two shipments via factory
        $s1 = Shipment::factory()->create(['purchase_order_id' => $po->id, 'status' => 'pending']);
        $s2 = Shipment::factory()->create(['purchase_order_id' => $po->id, 'status' => 'pending']);

        // list shipments
        $listResp = $this->actingAs($admin)->withoutMiddleware()->get('/admin/purchase-orders/'. $po->id .'/shipments');
        $listResp->assertStatus(200);
        $data = $listResp->json();
        $this->assertCount(2, $data['shipments']);

        // mark first as shipped
        $shipResp = $this->actingAs($admin)->withoutMiddleware()->put('/admin/purchase-orders/'. $po->id .'/shipments/'. $s1->id_shipment .'/ship');
        $shipResp->assertStatus(200);
        $this->assertDatabaseHas('shipments', ['id_shipment' => $s1->id_shipment, 'status' => 'shipped']);

        // mark first as delivered
        $delResp = $this->actingAs($admin)->withoutMiddleware()->put('/admin/purchase-orders/'. $po->id .'/shipments/'. $s1->id_shipment .'/deliver');
        $delResp->assertStatus(200);
        $this->assertDatabaseHas('shipments', ['id_shipment' => $s1->id_shipment, 'status' => 'delivered']);

        // cancel second
        $cancelResp = $this->actingAs($admin)->withoutMiddleware()->put('/admin/purchase-orders/'. $po->id .'/shipments/'. $s2->id_shipment .'/cancel');
        $cancelResp->assertStatus(200);
        $this->assertDatabaseHas('shipments', ['id_shipment' => $s2->id_shipment, 'status' => 'cancelled']);
    }
}
