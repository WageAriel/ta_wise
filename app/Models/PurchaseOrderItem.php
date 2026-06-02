<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PurchaseOrderItem - The item/line detail dalam PurchaseOrder
 */
class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'barang_id',
        'id_item_type',
        'id_subtype',
        'quantity',
        'unit_price',
        'subtotal',
        'uom',
        'supplier_offered_price',
        'supplier_offered_quantity',
        'counter_offered_price',
        'counter_offered_quantity',
        'final_price',
        'final_quantity',
        'doc_verified',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'supplier_offered_price' => 'decimal:2',
        'supplier_offered_quantity' => 'integer',
        'counter_offered_price' => 'decimal:2',
        'counter_offered_quantity' => 'integer',
        'final_price' => 'decimal:2',
        'final_quantity' => 'integer',
        'doc_verified' => 'boolean',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'id_barang');
    }

    public function itemType()
    {
        return $this->belongsTo(POItemType::class, 'id_item_type', 'id_item_type');
    }

    public function subtype()
    {
        return $this->belongsTo(POItemSubtype::class, 'id_subtype', 'id_subtype');
    }
}

