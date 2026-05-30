<?php

namespace Tests\Feature;

use App\Models\POItemType;
use App\Models\POItemSubtype;
use App\Models\POItemUoMDefault;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    /**
     * Phase 5: Manajer Gudang - Admin dapat melihat daftar item types
     */
    public function test_admin_can_view_item_types_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create sample item type
        $itemType = POItemType::create([
            'type_name' => 'Item A',
            'description' => 'Category A Items',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->get('/manajer/purchase-order-config/item-types');

        $response->assertStatus(200);
        // Should contain item type list
    }

    /**
     * Admin dapat membuat item type baru
     */
    public function test_admin_can_create_item_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->post('/manajer/purchase-order-config/item-types', [
                'type_name' => 'Item C',
                'description' => 'Category C Items',
                'sort_order' => 3,
            ]);

        $response->assertRedirect('/manajer/purchase-order-config/item-types');

        $this->assertDatabaseHas('po_item_types', [
            'type_name' => 'Item C',
            'description' => 'Category C Items',
            'sort_order' => 3,
        ]);
    }

    /**
     * Admin dapat edit item type
     */
    public function test_admin_can_edit_item_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $itemType = POItemType::create([
            'type_name' => 'Item A',
            'description' => 'Original Description',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->put("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}", [
                'type_name' => 'Item A - Updated',
                'description' => 'Updated Description',
                'sort_order' => 2,
            ]);

        $response->assertRedirect('/manajer/purchase-order-config/item-types');

        $itemType->refresh();
        $this->assertSame('Item A - Updated', $itemType->type_name);
        $this->assertSame('Updated Description', $itemType->description);
        $this->assertSame(2, $itemType->sort_order);
    }

    /**
     * Admin dapat delete item type
     */
    public function test_admin_can_delete_item_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $itemType = POItemType::create([
            'type_name' => 'Item A',
            'description' => 'To Be Deleted',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->delete("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}");

        $response->assertRedirect('/manajer/purchase-order-config/item-types');

        $this->assertDatabaseMissing('po_item_types', [
            'id_item_type' => $itemType->id_item_type,
        ]);
    }

    /**
     * Admin dapat melihat subtypes untuk item type tertentu
     */
    public function test_admin_can_view_item_subtypes_for_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $itemType = POItemType::create([
            'type_name' => 'Item A',
            'description' => 'Category A',
            'sort_order' => 1,
        ]);

        $subtype = POItemSubtype::create([
            'id_item_type' => $itemType->id_item_type,
            'subtype_name' => 'A1',
            'description' => 'Item A variant 1',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->get("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}/subtypes");

        $response->assertStatus(200);
        // Should contain subtype list
    }

    /**
     * Admin dapat membuat subtype baru
     */
    public function test_admin_can_create_item_subtype(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $itemType = POItemType::create([
            'type_name' => 'Item A',
            'description' => 'Category A',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->post("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}/subtypes", [
                'subtype_name' => 'A2',
                'description' => 'Item A variant 2',
                'sort_order' => 2,
            ]);

        $response->assertRedirect("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}/subtypes");

        $this->assertDatabaseHas('po_item_subtypes', [
            'id_item_type' => $itemType->id_item_type,
            'subtype_name' => 'A2',
        ]);
    }

    /**
     * Admin dapat edit subtype
     */
    public function test_admin_can_edit_item_subtype(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $itemType = POItemType::create([
            'type_name' => 'Item A',
            'description' => 'Category A',
            'sort_order' => 1,
        ]);

        $subtype = POItemSubtype::create([
            'id_item_type' => $itemType->id_item_type,
            'subtype_name' => 'A1',
            'description' => 'Original',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->put("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}/subtypes/{$subtype->id_subtype}", [
                'subtype_name' => 'A1 - Premium',
                'description' => 'Updated variant 1',
                'sort_order' => 1,
            ]);

        $response->assertRedirect("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}/subtypes");

        $subtype->refresh();
        $this->assertSame('A1 - Premium', $subtype->subtype_name);
        $this->assertSame('Updated variant 1', $subtype->description);
    }

    /**
     * Admin dapat delete subtype
     */
    public function test_admin_can_delete_item_subtype(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $itemType = POItemType::create([
            'type_name' => 'Item A',
            'description' => 'Category A',
            'sort_order' => 1,
        ]);

        $subtype = POItemSubtype::create([
            'id_item_type' => $itemType->id_item_type,
            'subtype_name' => 'A1',
            'description' => 'To Delete',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->delete("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}/subtypes/{$subtype->id_subtype}");

        $response->assertRedirect("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}/subtypes");

        $this->assertDatabaseMissing('po_item_subtypes', [
            'id_subtype' => $subtype->id_subtype,
        ]);
    }

    /**
     * Admin dapat melihat dan set UoM default untuk item type
     */
    public function test_admin_can_configure_uom_defaults(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $itemType = POItemType::create([
            'type_name' => 'Item A',
            'description' => 'Category A',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->post("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}/uom", [
                'default_uom' => 'pcs',
                'force_uom' => true,
            ]);

        $response->assertRedirect("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}/subtypes");

        $this->assertDatabaseHas('po_item_uom_defaults', [
            'id_item_type' => $itemType->id_item_type,
            'default_uom' => 'pcs',
            'force_uom' => true,
        ]);
    }

    /**
     * Admin dapat edit UoM default
     */
    public function test_admin_can_edit_uom_defaults(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $itemType = POItemType::create([
            'type_name' => 'Item A',
            'description' => 'Category A',
            'sort_order' => 1,
        ]);

        $uomConfig = POItemUoMDefault::create([
            'id_item_type' => $itemType->id_item_type,
            'default_uom' => 'kg',
            'force_uom' => false,
        ]);

        $response = $this->actingAs($admin)->withoutMiddleware()
            ->put("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}/uom/{$uomConfig->id_uom_config}", [
                'default_uom' => 'box',
                'force_uom' => true,
            ]);

        $response->assertRedirect("/manajer/purchase-order-config/item-types/{$itemType->id_item_type}/subtypes");

        $uomConfig->refresh();
        $this->assertSame('box', $uomConfig->default_uom);
        $this->assertTrue($uomConfig->force_uom);
    }
}
