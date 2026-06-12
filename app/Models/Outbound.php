<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outbound extends Model
{
    use HasFactory;

    protected $table = 'outbounds';
    protected $primaryKey = 'id_outbound';

    protected $fillable = [
        'tanggal',
        'status',
        'tujuan',
        'id_user'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function details()
    {
        return $this->hasMany(OutboundDetail::class, 'id_outbound', 'id_outbound');
    }
}
