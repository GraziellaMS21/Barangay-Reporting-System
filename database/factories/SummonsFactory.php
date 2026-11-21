<?php

namespace Database\Factories;

use App\Models\BarangayCase;
use App\Models\CaseParty;
use App\Models\Summons;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Summons>
 */
class SummonsFactory extends Factory
{
    protected $model = Summons::class;

    public function definition(): array
    {
        return [
            'case_id' => BarangayCase::factory(),
            'respondent_case_party_id' => CaseParty::factory(),
            'sequence_no' => 1,
            'summons_number' => 'SUM-'.date('Y').'-'.fake()->unique()->numerify('####'),
            'issued_on' => now()->toDateString(),
            'issued_by' => User::factory(),
            'hearing_at' => now()->addDays(5),
            'venue' => 'Barangay Hall Mediation Room',
            'served_by_personnel_id' => null,
            'served_on' => null,
            'service_result' => 'pending',
            'attendance' => 'pending',
            'remarks' => null,
            'voided_at' => null,
            'void_note' => null,
            'override_note' => null,
        ];
    }
}
