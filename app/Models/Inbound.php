<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inbound extends Model
{
    use HasFactory;

    protected $table = 'inbounds';
    protected $primaryKey = 'id_inbound';
    public $incrementing = false; // Primary key is a string
    protected $keyType = 'string';

    protected $fillable = [
        'id_inbound',
        'purchase_order_id',
        'tanggal',
        'status',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function items()
    {
        return $this->hasMany(InboundItem::class, 'id_inbound', 'id_inbound');
    }

    public function putAways()
    {
        return $this->hasMany(PutAway::class, 'id_inbound', 'id_inbound');
    }

    public function returnBarang()
    {
        return $this->hasMany(ReturnBarang::class, 'id_inbound', 'id_inbound');
    }
}
