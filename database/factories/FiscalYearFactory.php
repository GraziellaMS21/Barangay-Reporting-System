<?php

namespace Database\Factories;

use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiscalYear>
 */
class FiscalYearFactory extends Factory
{
    protected $model = FiscalYear::class;

    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2025, 2040);

        return [
            'year' => $year,
            'label' => "FY {$year}",
            'starts_on' => "{$year}-01-01",
            'ends_on' => "{$year}-12-31",
            'status' => 'inactive',
            'is_locked' => false,
            'closed_at' => null,
            'closed_by' => null,
            'close_note' => null,
        ];
    }
}
