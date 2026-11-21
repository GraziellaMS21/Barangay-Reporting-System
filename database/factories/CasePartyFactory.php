<?php

namespace Database\Factories;

use App\Models\BarangayCase;
use App\Models\CaseParty;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseParty>
 */
class CasePartyFactory extends Factory
{
    protected $model = CaseParty::class;

    public function definition(): array
    {
        return [
            'case_id' => BarangayCase::factory(),
            'party_id' => Party::factory(),
            'role' => fake()->randomElement(['complainant', 'respondent', 'witness']),
            'residency_at_filing' => 'resident',
            'representative_name' => null,
            'notes' => null,
        ];
    }
}
