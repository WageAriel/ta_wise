<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    // Order Request Segment
    public const STATUS_DRAFT = 'draft'; // Local draft before submission
    public const STATUS_INQUIRY = 'inquiry'; // Draft PO, supplier not selected
    public const STATUS_SUBMITTED = 'submitted'; // Submitted for processing
    public const STATUS_RFQ = 'rfq'; // Request For Quotation, sent to supplier

    // Waiting List Segment
    public const STATUS_VERIFICATION = 'verification'; // Awaiting supplier verification
    public const STATUS_REQUEST = 'request'; // Supplier submitted counter offer
    public const STATUS_COMPLETENESS = 'completeness'; // Document check stage

    // Order List Segment
    public const STATUS_APPROVED = 'approved'; // Ready for shipment
    public const STATUS_SHIPMENT = 'shipment'; // In transit
    public const STATUS_COMPLETED = 'completed'; // Received

    // Terminal states
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'po_number',
        'supplier_id',
        'user_id',
        'date',
        'status',
        'description',
        'document_path',
        'total_price',
        'delivery_type',
        'driver_name',
        'vehicle_plate',
        'phone_number',
        'courier_provider',
        'tracking_number',
        'shipment_notes',
        'supplementary_doc_path',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'date' => 'date',
        'total_price' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Format the date automatically when serialized to JSON.
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d/H:i \W\I\B');
    }

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_INQUIRY,
            self::STATUS_SUBMITTED,
            self::STATUS_RFQ,
            self::STATUS_VERIFICATION,
            self::STATUS_REQUEST,
            self::STATUS_COMPLETENESS,
            self::STATUS_APPROVED,
            self::STATUS_SHIPMENT,
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function statusesForSegment(?string $segment): array
    {
        return match ($segment) {
            'order-request' => [
                self::STATUS_DRAFT,
                self::STATUS_INQUIRY,
                self::STATUS_SUBMITTED,
                self::STATUS_RFQ,
            ],
            'waiting-list' => [
                self::STATUS_RFQ,
                self::STATUS_VERIFICATION,
                self::STATUS_REQUEST,
                self::STATUS_COMPLETENESS,
            ],
            'order-list' => [
                self::STATUS_APPROVED,
                self::STATUS_SHIPMENT,
                self::STATUS_COMPLETED,
            ],
            default => [],
        };
    }

    public function scopeForSegment($query, ?string $segment)
    {
        $statuses = self::statusesForSegment($segment);

        if ($statuses === []) {
            return $query;
        }

        $query->whereIn('status', $statuses);

        if ($segment === 'order-list') {
            return $query->orderByRaw("case status when ? then 1 when ? then 2 when ? then 3 end", [
                self::STATUS_SHIPMENT,
                self::STATUS_APPROVED,
                self::STATUS_COMPLETED,
            ]);
        }

        return $query;
    }

    public static function generatePoNumber(): string
    {
        return 'PO-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
    }

    public function recalculateTotal(): void
    {
        $this->update([
            'total_price' => $this->items()->sum('subtotal'),
        ]);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getShipmentStateLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Masih Diproses',
            self::STATUS_SHIPMENT => 'Dalam Perjalanan',
            self::STATUS_COMPLETED => 'Sudah Diterima',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
