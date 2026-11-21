<?php

namespace Database\Factories;

use App\Models\EscalationDestination;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EscalationDestination>
 */
class EscalationDestinationFactory extends Factory
{
    protected $model = EscalationDestination::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['City Prosecutor Office', 'Municipal Trial Court in Cities (MTCC)', 'PNP Station 8']),
            'office_id' => Office::first()?->id ?? null,
            'sort_order' => 1,
            'is_active' => true,
            'is_confirmed' => false,
        ];
    }
}
