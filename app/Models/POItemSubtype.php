<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * POItemSubtype - Sub-klasifikasi item (A1, A2, A3, B1, B2, B3, dll)
 */
class POItemSubtype extends Model
{
    use HasFactory;

    protected $table = 'po_item_subtypes';
    protected $primaryKey = 'id_subtype';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'id_item_type',
        'subtype_name',
        'description',
        'sort_order',
    ];

    public function itemType()
    {
        return $this->belongsTo(POItemType::class, 'id_item_type', 'id_item_type');
    }
}
