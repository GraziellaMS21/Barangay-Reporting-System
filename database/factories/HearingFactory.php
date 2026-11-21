<?php

namespace Database\Factories;

use App\Models\BarangayCase;
use App\Models\Hearing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hearing>
 */
class HearingFactory extends Factory
{
    protected $model = Hearing::class;

    public function definition(): array
    {
        return [
            'case_id' => BarangayCase::factory(),
            'summons_id' => null,
            'scheduled_at' => now()->addDays(7),
            'venue' => 'Barangay Hall Mediation Room',
            'presided_by_personnel_id' => null,
            'status' => 'scheduled',
            'outcome_note' => null,
        ];
    }
}
