<?php

namespace Database\Factories;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\Purok;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BarangayCase>
 */
class BarangayCaseFactory extends Factory
{
    protected $model = BarangayCase::class;

    public function definition(): array
    {
        $year = (int) date('Y');
        $fy = FiscalYear::firstOrCreate(
            ['year' => $year],
            [
                'label' => "FY {$year}",
                'starts_on' => "{$year}-01-01",
                'ends_on' => "{$year}-12-31",
                'status' => 'active',
                'is_locked' => false,
            ]
        );

        return [
            'case_number' => "CASE-{$year}-".fake()->unique()->numerify('#####'),
            'fiscal_year_id' => $fy->id,
            'case_type_id' => CaseType::factory(),
            'source' => 'walk_in',
            'source_report_id' => null,
            'title' => 'Dispute between neighbors regarding boundary line',
            'narrative' => fake()->paragraphs(2, true),
            'filed_on' => now()->toDateString(),
            'incident_date' => now()->subDays(3)->toDateString(),
            'incident_location' => 'Purok 2, Calarian, Zamboanga City',
            'purok_id' => Purok::first()?->id ?? null,
            'status' => CaseStatus::FILED,
            'encoded_by' => User::factory(),
            'assigned_to' => null,
            'settled_on' => null,
            'settlement_terms' => null,
            'outcome_note' => null,
            'is_confidential' => false,
        ];
    }
}
