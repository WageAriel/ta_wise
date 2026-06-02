<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $description
 */
class PurchaseOrderSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'supplier_description',
        'admin_description',
        'uom_options',
        'updated_by',
    ];

    protected $casts = [
        'uom_options' => 'array',
    ];

    public static function current(): self
    {
        $setting = self::query()->first();

        if (!$setting) {
            $setting = self::create([
                'description' => self::defaultDescription(),
                'supplier_description' => self::defaultDescription(),
                'admin_description' => null,
                'uom_options' => self::defaultUomOptions(),
            ]);
        }

        if (blank($setting->supplier_description)) {
            $setting->supplier_description = $setting->description ?: self::defaultDescription();
        }

        if (! $setting->admin_description) {
            $setting->admin_description = null;
        }

        if (empty($setting->uom_options)) {
            $setting->uom_options = self::defaultUomOptions();
        }

        return $setting;
    }

    public static function defaultDescription(): string
    {
        return 'Lorem ipsum dolor sit amet consectetur adipisicing elit.';
    }

    public static function defaultUomOptions(): array
    {
        return ['pcs', 'box', 'pack', 'karton', 'lusin', 'kg', 'gram', 'liter', 'ml', 'roll'];
    }
}
