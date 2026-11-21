<?php

namespace Database\Factories;

use App\Models\FiscalYear;
use App\Models\GeneratedReport;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneratedReport>
 */
class GeneratedReportFactory extends Factory
{
    protected $model = GeneratedReport::class;

    public function definition(): array
    {
        return [
            'report_template_id' => ReportTemplate::factory(),
            'report_key' => fake()->slug(),
            'params' => null,
            'format' => 'pdf',
            'fiscal_year_id' => FiscalYear::factory(),
            'generated_by' => User::factory(),
        ];
    }
}
