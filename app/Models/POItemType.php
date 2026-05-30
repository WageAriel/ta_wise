<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * POItemType - Klasifikasi item untuk PO (Item A, Item B, dll)
 * Dikelola oleh Manajer Gudang di PO Controller
 */
class POItemType extends Model
{
    use HasFactory;

    protected $table = 'po_item_types';
    protected $primaryKey = 'id_item_type';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'type_name',
        'description',
        'sort_order',
    ];

    public function subtypes()
    {
        return $this->hasMany(POItemSubtype::class, 'id_item_type', 'id_item_type')
            ->orderBy('sort_order');
    }

    public function uomConfig()
    {
        return $this->hasOne(POItemUoMDefault::class, 'id_item_type', 'id_item_type');
    }
}
