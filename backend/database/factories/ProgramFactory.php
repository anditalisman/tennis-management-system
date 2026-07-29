<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Program '.fake()->words(2, true),
            'age_group' => fake()->randomElement(['anak', 'remaja', 'dewasa']),
            'skill_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'status' => Program::STATUS_ACTIVE,
        ];
    }
}
