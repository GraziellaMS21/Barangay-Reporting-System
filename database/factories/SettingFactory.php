<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'type' => 'string',
            'group' => 'general',
            'label' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'value' => fake()->word(),
            'is_confirmed' => false,
            'confirmed_by' => null,
            'confirmed_at' => null,
            'source_question' => null,
        ];
    }
}
