<?php

namespace Database\Factories;

use App\Models\BarangayCase;
use App\Models\CaseStatusLog;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseStatusLog>
 */
class CaseStatusLogFactory extends Factory
{
    protected $model = CaseStatusLog::class;

    public function definition(): array
    {
        return [
            'case_id' => BarangayCase::factory(),
            'user_id' => User::factory(),
            'from_status' => null,
            'to_status' => CaseStatus::FILED,
            'note' => 'Initial case filing recorded.',
            'created_at' => now(),
        ];
    }
}
