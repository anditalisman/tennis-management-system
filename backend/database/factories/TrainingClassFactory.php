<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Program;
use App\Models\TrainingClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingClass>
 */
class TrainingClassFactory extends Factory
{
    protected $model = TrainingClass::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'branch_id' => Branch::factory(),
            'name' => 'Kelas '.fake()->words(2, true),
            'capacity_min' => 1,
            'capacity_max' => 8,
            'session_duration' => 60,
            'status' => TrainingClass::STATUS_ACTIVE,
        ];
    }
}
