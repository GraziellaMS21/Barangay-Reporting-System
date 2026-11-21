<?php

namespace Database\Factories;

use App\Models\ReportTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportTemplate>
 */
class ReportTemplateFactory extends Factory
{
    protected $model = ReportTemplate::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'source' => 'cases',
            'columns' => ['case_number', 'filed_on', 'status'],
            'filters' => null,
            'orientation' => 'portrait',
            'signatory_document_type' => null,
            'is_active' => true,
            'is_confirmed' => false,
        ];
    }
}
