<?php

namespace Database\Factories;

use App\Models\Coach;
use App\Models\Court;
use App\Models\TrainingClass;
use App\Models\TrainingSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSchedule>
 */
class TrainingScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'class_id' => TrainingClass::factory(),
            'court_id' => Court::factory(),
            'coach_id' => Coach::factory(),
            'session_date' => fake()->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d'),
            'start_time' => '16:00',
            'end_time' => '17:00',
            'type' => TrainingSchedule::TYPE_REGULAR,
            'status' => TrainingSchedule::STATUS_SCHEDULED,
        ];
    }
}
