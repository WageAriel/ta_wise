<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundRecipient extends Model
{
    protected $table = 'outbound_recipients';
    protected $primaryKey = 'id_recipient';

    protected $fillable = [
        'nama_penerima',
        'alamat_tujuan',
        'kota_tujuan',
        'telepon_penerima',
        'keterangan_tujuan',
    ];
}
