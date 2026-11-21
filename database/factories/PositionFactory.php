<?php

namespace Database\Factories;

use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'name' => $name,
            'code' => fake()->unique()->slug(2),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_signatory_default' => false,
            'is_active' => true,
            'is_confirmed' => false,
        ];
    }
}
