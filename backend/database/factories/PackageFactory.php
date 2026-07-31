<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Paket '.fake()->numberBetween(4, 20).'x Sesi',
            'session_count' => fake()->numberBetween(4, 20),
            'validity_days' => 90,
            'price' => fake()->numberBetween(500, 5000) * 1000,
            'type' => 'regular',
            'status' => Package::STATUS_ACTIVE,
        ];
    }
}
