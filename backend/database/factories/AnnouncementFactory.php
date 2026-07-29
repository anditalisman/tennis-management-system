<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'body' => fake()->paragraph(),
            'target_type' => Announcement::TARGET_ALL,
            'status' => Announcement::STATUS_PUBLISHED,
            'publish_at' => now()->subDay(),
            'created_by' => User::factory(),
        ];
    }
}
