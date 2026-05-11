<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@wise.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        \App\Models\User::factory()->create([
            'username' => 'supplier1',
            'email' => 'supplier1@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'supplier',
            'is_active' => true,
        ]);

        \App\Models\User::factory()->create([
            'username' => 'petugas',
            'email' => 'petugas@wise.com',
            'password' => bcrypt('password'),
            'role' => 'petugas_lapangan',
            'is_active' => true,
        ]);
        
        \App\Models\User::factory()->create([
            'username' => 'manajer',
            'email' => 'manajer@wise.com',
            'password' => bcrypt('password'),
            'role' => 'manajer',
            'is_active' => true,
        ]);
    }
}
