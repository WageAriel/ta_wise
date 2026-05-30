<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'po_number' => 'PO-' . $this->faker->unique()->numberBetween(1000, 9999),
            'supplier_id' => \App\Models\Supplier::factory(),
            'user_id' => \App\Models\User::factory(),
            'date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['draft', 'inquiry', 'request_for_quotation']),
            'description' => $this->faker->sentence(),
            'total_price' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }
}
