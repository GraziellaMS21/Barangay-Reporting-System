<?php

namespace Database\Factories;

use App\Models\ResponseTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResponseTemplate>
 */
class ResponseTemplateFactory extends Factory
{
    protected $model = ResponseTemplate::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'name' => fake()->words(3, true),
            'status' => 'in_progress',
            'category_id' => null,
            'kind' => 'status_update',
            'body' => fake()->paragraph(),
            'is_active' => true,
            'created_by' => null,
        ];
    }
}
