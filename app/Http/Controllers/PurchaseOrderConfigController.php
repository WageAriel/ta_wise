<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePOItemTypeRequest;
use App\Http\Requests\UpdatePOItemTypeRequest;
use App\Http\Requests\StorePOItemSubtypeRequest;
use App\Http\Requests\UpdatePOItemSubtypeRequest;
use App\Http\Requests\StorePOItemUoMRequest;
use App\Http\Requests\UpdatePOItemUoMRequest;
use App\Models\POItemType;
use App\Models\POItemSubtype;
use App\Models\POItemUoMDefault;
use Illuminate\Support\Facades\DB;

/**
 * PurchaseOrderConfigController - Manajer Gudang configuration for PO item types, subtypes, UoM defaults
 * Phase 5: Configuration management interface
 */
class PurchaseOrderConfigController extends Controller
{
    // ==================== ITEM TYPE MANAGEMENT ====================

    /**
     * View all item types (POItemType list)
     */
    public function indexItemTypes()
    {
        $itemTypes = POItemType::orderBy('sort_order')->get();

        return response()->json([
            'item_types' => $itemTypes,
        ]);
    }

    /**
     * Store new item type
     */
    public function storeItemType(StorePOItemTypeRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            POItemType::create($validated);
        });

        return redirect('/manajer/purchase-order-config/item-types')
            ->with('success', "Tipe item '{$validated['type_name']}' berhasil dibuat.");
    }

    /**
     * Update item type
     */
    public function updateItemType($id, UpdatePOItemTypeRequest $request)
    {
        $itemType = POItemType::findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($itemType, $validated) {
            $itemType->update($validated);
        });

        return redirect('/manajer/purchase-order-config/item-types')
            ->with('success', "Tipe item '{$validated['type_name']}' berhasil diperbarui.");
    }

    /**
     * Delete item type
     */
    public function destroyItemType($id)
    {
        $itemType = POItemType::findOrFail($id);

        DB::transaction(function () use ($itemType) {
            $itemType->delete();
        });

        return redirect('/manajer/purchase-order-config/item-types')
            ->with('success', "Tipe item '{$itemType->type_name}' berhasil dihapus.");
    }

    // ==================== ITEM SUBTYPE MANAGEMENT ====================

    /**
     * View subtypes for specific item type
     */
    public function indexSubtypes($itemTypeId)
    {
        $itemType = POItemType::findOrFail($itemTypeId);
        $subtypes = $itemType->subtypes()->orderBy('sort_order')->get();
        $uomConfig = POItemUoMDefault::where('id_item_type', $itemTypeId)->first();

        return response()->json([
            'item_type' => $itemType,
            'subtypes' => $subtypes,
            'uom_config' => $uomConfig,
        ]);
    }

    /**
     * Store new subtype for item type
     */
    public function storeSubtype($itemTypeId, StorePOItemSubtypeRequest $request)
    {
        $itemType = POItemType::findOrFail($itemTypeId);
        $validated = $request->validated();

        DB::transaction(function () use ($itemType, $validated) {
            $itemType->subtypes()->create($validated);
        });

        return redirect("/manajer/purchase-order-config/item-types/{$itemTypeId}/subtypes")
            ->with('success', "Subtype '{$validated['subtype_name']}' berhasil dibuat.");
    }

    /**
     * Update subtype
     */
    public function updateSubtype($itemTypeId, $subtypeId, UpdatePOItemSubtypeRequest $request)
    {
        $itemType = POItemType::findOrFail($itemTypeId);
        $subtype = $itemType->subtypes()->findOrFail($subtypeId);
        $validated = $request->validated();

        DB::transaction(function () use ($subtype, $validated) {
            $subtype->update($validated);
        });

        return redirect("/manajer/purchase-order-config/item-types/{$itemTypeId}/subtypes")
            ->with('success', "Subtype '{$validated['subtype_name']}' berhasil diperbarui.");
    }

    /**
     * Delete subtype
     */
    public function destroySubtype($itemTypeId, $subtypeId)
    {
        $itemType = POItemType::findOrFail($itemTypeId);
        $subtype = $itemType->subtypes()->findOrFail($subtypeId);
        $subtypeName = $subtype->subtype_name;

        DB::transaction(function () use ($subtype) {
            $subtype->delete();
        });

        return redirect("/manajer/purchase-order-config/item-types/{$itemTypeId}/subtypes")
            ->with('success', "Subtype '{$subtypeName}' berhasil dihapus.");
    }

    // ==================== UoM CONFIGURATION ====================

    /**
     * Store UoM default for item type (create if not exists)
     */
    public function storeUoM($itemTypeId, StorePOItemUoMRequest $request)
    {
        $itemType = POItemType::findOrFail($itemTypeId);
        $validated = $request->validated();

        DB::transaction(function () use ($itemType, $validated) {
            // Delete old config if exists
            POItemUoMDefault::where('id_item_type', $itemType->id_item_type)->delete();

            // Create new config
            POItemUoMDefault::create(
                array_merge($validated, ['id_item_type' => $itemType->id_item_type])
            );
        });

        return redirect("/manajer/purchase-order-config/item-types/{$itemTypeId}/subtypes")
            ->with('success', "Konfigurasi UoM untuk '{$itemType->type_name}' berhasil disimpan.");
    }

    /**
     * Update UoM default (update if exists)
     */
    public function updateUoM($itemTypeId, $uomConfigId, UpdatePOItemUoMRequest $request)
    {
        $itemType = POItemType::findOrFail($itemTypeId);
        $uomConfig = POItemUoMDefault::findOrFail($uomConfigId);
        $validated = $request->validated();

        DB::transaction(function () use ($uomConfig, $validated) {
            $uomConfig->update($validated);
        });

        return redirect("/manajer/purchase-order-config/item-types/{$itemTypeId}/subtypes")
            ->with('success', "Konfigurasi UoM untuk '{$itemType->type_name}' berhasil diperbarui.");
    }
}
