<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement(['Raket', 'Bola Tenis', 'Seragam', 'Cone']),
            'category' => fake()->randomElement(['equipment', 'apparel']),
            'stock_qty' => fake()->numberBetween(5, 50),
            'condition' => InventoryItem::CONDITION_GOOD,
        ];
    }
}
