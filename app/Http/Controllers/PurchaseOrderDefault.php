<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePOItemSubtypeRequest;
use App\Http\Requests\StorePOItemTypeRequest;
use App\Http\Requests\StorePOItemUoMRequest;
use App\Http\Requests\UpdatePOItemSubtypeRequest;
use App\Http\Requests\UpdatePOItemTypeRequest;
use App\Http\Requests\UpdatePOItemUoMRequest;
use App\Models\POItemSubtype;
use App\Models\POItemType;
use App\Models\POItemUoMDefault;
use App\Models\PurchaseOrderSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * PurchaseOrderDefault - Default configuration untuk Purchase Order
 */
class PurchaseOrderDefault extends Controller
{
    public function page()
    {
        $settings = PurchaseOrderSetting::current();
        $itemTypes = POItemType::with(['subtypes', 'uomConfig'])->orderBy('sort_order')->get();

        return Inertia::render('Manajer/PurchaseOrdersController/SplitIndex', [
            'settings' => $settings,
            'itemTypes' => $itemTypes,
            'segment' => request()->query('segment', 'supplier'),
            'uomOptions' => $settings->uom_options ?: PurchaseOrderSetting::defaultUomOptions(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
            'supplier_description' => ['nullable', 'string', 'max:2000'],
            'admin_description' => ['nullable', 'string', 'max:2000'],
            'uom_options' => ['nullable', 'array'],
            'uom_options.*' => ['nullable', 'string', 'max:50'],
            'limit_class_a' => ['required', 'integer', 'min:1'],
            'limit_class_b' => ['required', 'integer', 'min:1', 'lte:limit_class_a'],
            'limit_class_c' => ['required', 'integer', 'min:1', 'lte:limit_class_b'],
        ], [
            'limit_class_b.lte' => 'Batas transaksi Kelas B tidak boleh lebih besar dari Kelas A.',
            'limit_class_c.lte' => 'Batas transaksi Kelas C tidak boleh lebih besar dari Kelas B.',
        ]);

        $settings = PurchaseOrderSetting::current();
        $settings->update([
            'description' => $validated['description'] ?? $settings->description ?? PurchaseOrderSetting::defaultDescription(),
            'supplier_description' => $validated['supplier_description'] ?? $settings->supplier_description ?? PurchaseOrderSetting::defaultDescription(),
            'admin_description' => $validated['admin_description'] ?? $settings->admin_description,
            'uom_options' => array_values(array_filter($validated['uom_options'] ?? $settings->uom_options ?? PurchaseOrderSetting::defaultUomOptions())),
            'limit_class_a' => $validated['limit_class_a'] ?? 1000,
            'limit_class_b' => $validated['limit_class_b'] ?? 500,
            'limit_class_c' => $validated['limit_class_c'] ?? 100,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('manajer.purchase-order-controller.index')
            ->with('success', 'Deskripsi Purchase Order berhasil diperbarui.');
    }

    public function indexItemTypes()
    {
        $itemTypes = POItemType::orderBy('sort_order')->get();

        return response()->json([
            'item_types' => $itemTypes,
        ]);
    }

    public function storeItemType(StorePOItemTypeRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $type = POItemType::create($validated);
            \App\Models\Barang::create([
                'nama_barang' => $type->type_name,
                'satuan' => 'pcs',
                'status' => 'Aktif',
                'id_item_type' => $type->id_item_type,
            ]);
        });

        return redirect()->back()->with('success', "Tipe item '{$validated['type_name']}' berhasil dibuat.");
    }

    public function updateItemType($id, UpdatePOItemTypeRequest $request)
    {
        $itemType = POItemType::findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($itemType, $validated) {
            $itemType->update($validated);
            $barang = \App\Models\Barang::where('id_item_type', $itemType->id_item_type)->first();
            if ($barang) {
                $barang->update([
                    'nama_barang' => $itemType->type_name,
                ]);
            } else {
                \App\Models\Barang::create([
                    'nama_barang' => $itemType->type_name,
                    'satuan' => 'pcs',
                    'status' => 'Aktif',
                    'id_item_type' => $itemType->id_item_type,
                ]);
            }
        });

        return redirect()->back()->with('success', "Tipe item '{$validated['type_name']}' berhasil diperbarui.");
    }

    public function destroyItemType($id)
    {
        $itemType = POItemType::findOrFail($id);

        DB::transaction(function () use ($itemType) {
            \App\Models\Barang::where('id_item_type', $itemType->id_item_type)->delete();
            $itemType->delete();
        });

        return redirect()->back()->with('success', "Tipe item '{$itemType->type_name}' berhasil dihapus.");
    }

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

    public function storeSubtype($itemTypeId, StorePOItemSubtypeRequest $request)
    {
        $itemType = POItemType::findOrFail($itemTypeId);
        $validated = $request->validated();

        DB::transaction(function () use ($itemType, $validated) {
            $itemType->subtypes()->create($validated);
        });

        return redirect()->back()->with('success', "Subtype '{$validated['subtype_name']}' berhasil dibuat.");
    }

    public function updateSubtype($itemTypeId, $subtypeId, UpdatePOItemSubtypeRequest $request)
    {
        $itemType = POItemType::findOrFail($itemTypeId);
        $subtype = $itemType->subtypes()->findOrFail($subtypeId);
        $validated = $request->validated();

        DB::transaction(function () use ($subtype, $validated) {
            $subtype->update($validated);
        });

        return redirect()->back()->with('success', "Subtype '{$validated['subtype_name']}' berhasil diperbarui.");
    }

    public function destroySubtype($itemTypeId, $subtypeId)
    {
        $itemType = POItemType::findOrFail($itemTypeId);
        $subtype = $itemType->subtypes()->findOrFail($subtypeId);
        $subtypeName = $subtype->subtype_name;

        DB::transaction(function () use ($subtype) {
            $subtype->delete();
        });

        return redirect()->back()->with('success', "Subtype '{$subtypeName}' berhasil dihapus.");
    }

    public function storeUoM($itemTypeId, StorePOItemUoMRequest $request)
    {
        $itemType = POItemType::findOrFail($itemTypeId);
        $validated = $request->validated();

        DB::transaction(function () use ($itemType, $validated) {
            POItemUoMDefault::where('id_item_type', $itemType->id_item_type)->delete();
            POItemUoMDefault::create(array_merge($validated, ['id_item_type' => $itemType->id_item_type]));

            $barang = \App\Models\Barang::where('id_item_type', $itemType->id_item_type)->first();
            if ($barang) {
                $barang->update([
                    'satuan' => $validated['default_uom'],
                ]);
            }
        });

        return redirect()->back()->with('success', "Konfigurasi UoM untuk '{$itemType->type_name}' berhasil disimpan.");
    }

    public function updateUoM($itemTypeId, $uomConfigId, UpdatePOItemUoMRequest $request)
    {
        $itemType = POItemType::findOrFail($itemTypeId);
        $uomConfig = POItemUoMDefault::findOrFail($uomConfigId);
        $validated = $request->validated();

        DB::transaction(function () use ($itemType, $uomConfig, $validated) {
            $uomConfig->update($validated);

            $barang = \App\Models\Barang::where('id_item_type', $itemType->id_item_type)->first();
            if ($barang) {
                $barang->update([
                    'satuan' => $validated['default_uom'],
                ]);
            }
        });

        return redirect()->back()->with('success', "Konfigurasi UoM untuk '{$itemType->type_name}' berhasil diperbarui.");
    }

    public function itemTypeCatalog()
    {
        return response()->json(
            POItemType::with(['subtypes', 'uomConfig'])->orderBy('sort_order')->get()
        );
    }
}