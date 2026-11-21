<?php

namespace Database\Factories;

use App\Models\BarangayCase;
use App\Models\CaseEscalation;
use App\Models\EscalationDestination;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseEscalation>
 */
class CaseEscalationFactory extends Factory
{
    protected $model = CaseEscalation::class;

    public function definition(): array
    {
        return [
            'case_id' => BarangayCase::factory(),
            'escalation_destination_id' => EscalationDestination::factory(),
            'escalated_by' => User::factory(),
            'decided_by_personnel_id' => null,
            'reason' => 'Parties failed to reach amicable settlement after multiple conciliation attempts.',
            'override_note' => null,
            'summons_count_at_time' => 3,
            'referral_id' => null,
            'escalated_at' => now(),
        ];
    }
}
