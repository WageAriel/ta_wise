<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'nama_perusahaan' => $this->faker->company(),
            'no_telp_perusahaan' => $this->faker->phoneNumber(),
            'alamat_perusahaan' => $this->faker->address(),
            'email_perusahaan' => $this->faker->companyEmail(),
            'nama_pic' => $this->faker->name(),
            'no_telp_pic' => $this->faker->phoneNumber(),
            'email_pic' => $this->faker->email(),
            'nama_bank' => $this->faker->word(),
            'no_rekening' => $this->faker->bankAccountNumber(),
            'atas_nama' => $this->faker->name(),
            'tahun_periode' => $this->faker->year(),
            'status' => $this->faker->randomElement(['draft', 'submitted', 'approved', 'rejected']),
        ];
    }
}
