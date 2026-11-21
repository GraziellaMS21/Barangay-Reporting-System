<?php

namespace Database\Factories;

use App\Models\CaseFieldConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseFieldConfig>
 */
class CaseFieldConfigFactory extends Factory
{
    protected $model = CaseFieldConfig::class;

    public function definition(): array
    {
        return [
            'entity' => fake()->randomElement(['party', 'case', 'summons']),
            'field_key' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'is_required' => false,
            'is_visible' => true,
            'applies_to' => 'all',
            'sort_order' => 0,
            'is_confirmed' => false,
        ];
    }
}
