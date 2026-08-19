<?php

namespace Database\Factories;

use App\Models\TournamentParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentParticipant>
 */
class TournamentParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'nickname' => fake()->firstName(),
            'phone' => fake()->numerify('08##########'),
            'category' => fake()->randomElement([
                TournamentParticipant::CATEGORY_BEGINNER,
                TournamentParticipant::CATEGORY_UPPER_BEGINNER,
            ]),
        ];
    }
}
