<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'birth_date' => fake()->dateTimeBetween('-40 years', '-6 years'),
            'age_category' => Participant::AGE_DEWASA,
            'gender' => fake()->randomElement(['male', 'female']),
            'skill_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'phone' => fake()->phoneNumber(),
            'status' => Participant::STATUS_PENDING_VERIFICATION,
            'policy_accepted_at' => now(),
        ];
    }
}
