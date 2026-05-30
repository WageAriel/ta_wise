<?php

namespace Database\Factories;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => \App\Models\PurchaseOrder::factory(),
            'carrier' => $this->faker->randomElement(['JNE','TIKI','SICEPAT']),
            'tracking_number' => strtoupper($this->faker->bothify('???#####')),
            'shipped_at' => null,
            'delivered_at' => null,
            'status' => 'pending',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
