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
        'no_outbound',
        'recipient_id',
        'nama_penerima',
        'alamat_tujuan',
        'kota_tujuan',
        'telepon_penerima',
        'keterangan_tujuan',
        'delivery_type',
        'nama_driver',
        'plat_nomor',
        'phone_number',
        'courier_provider',
        'no_resi',
        'tanggal_keluar',
        'catatan_pengiriman',
        'supplementary_doc_path',
        'created_by',
    ];

    protected $casts = [
        'tanggal_keluar' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(OutboundItem::class, 'id_outbound', 'id_outbound');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Generate nomor outbound unik: OB-YYYY-NNNNN
     */
    public static function generateNumber(): string
    {
        $year  = now()->format('Y');
        $last  = static::whereYear('created_at', $year)->count() + 1;
        return 'OB-' . $year . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
