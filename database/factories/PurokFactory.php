<?php

namespace Database\Factories;

use App\Models\Purok;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purok>
 */
class PurokFactory extends Factory
{
    protected $model = Purok::class;

    public function definition(): array
    {
        return [
            'name' => 'Purok ' . fake()->unique()->numberBetween(1, 1000),
        ];
    }
}
