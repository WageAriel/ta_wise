<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * POItemUoMDefault - Konfigurasi default Unit of Measure per item type
 */
class POItemUoMDefault extends Model
{
    use HasFactory;

    protected $table = 'po_item_uom_defaults';
    protected $primaryKey = 'id_uom_config';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'id_item_type',
        'default_uom',
        'force_uom',
    ];

    protected $casts = [
        'force_uom' => 'boolean',
    ];

    public function itemType()
    {
        return $this->belongsTo(POItemType::class, 'id_item_type', 'id_item_type');
    }
}
