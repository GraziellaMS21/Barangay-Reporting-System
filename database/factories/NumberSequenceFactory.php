<?php

namespace Database\Factories;

use App\Models\FiscalYear;
use App\Models\NumberSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NumberSequence>
 */
class NumberSequenceFactory extends Factory
{
    protected $model = NumberSequence::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['case', 'referral', 'summons']),
            'fiscal_year_id' => FiscalYear::factory(),
            'last_number' => fake()->numberBetween(1, 100),
        ];
    }
}
