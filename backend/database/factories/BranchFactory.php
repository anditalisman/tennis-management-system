<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Zul Tennis Clinic '.fake()->unique()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'status' => Branch::STATUS_ACTIVE,
        ];
    }
}
