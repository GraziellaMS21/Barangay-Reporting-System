<?php

namespace Database\Factories;

use App\Models\CaseType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CaseType>
 */
class CaseTypeFactory extends Factory
{
    protected $model = CaseType::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Neighborhood Boundary Dispute',
            'Noise Complaint and Disturbance',
            'Property Damage Dispute',
            'Minor Debt and Collection',
            'Verbal Altercation',
            'Tenancy Disagreement',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'sort_order' => 1,
            'is_active' => true,
            'is_confirmed' => false,
        ];
    }
}
