<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => 'Lapangan '.fake()->numberBetween(1, 8),
            'surface_type' => fake()->randomElement(['hard', 'clay', 'grass']),
            'status' => Court::STATUS_ACTIVE,
        ];
    }
}
