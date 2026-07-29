<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Coach;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coach>
 */
class CoachFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'employment_status' => Coach::STATUS_ACTIVE,
        ];
    }
}
