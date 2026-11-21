<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\SignatoryAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SignatoryAssignment>
 */
class SignatoryAssignmentFactory extends Factory
{
    protected $model = SignatoryAssignment::class;

    public function definition(): array
    {
        return [
            'document_type' => fake()->randomElement(['summons', 'endorsement', 'resolution', 'certificate']),
            'role_label' => fake()->randomElement(['Prepared by', 'Presided by', 'Approved by', 'Attested by']),
            'position_id' => Position::factory(),
            'sort_order' => 1,
            'is_confirmed' => false,
        ];
    }
}
