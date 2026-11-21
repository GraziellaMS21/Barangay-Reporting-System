<?php

namespace Database\Factories;

use App\Models\Personnel;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Personnel>
 */
class PersonnelFactory extends Factory
{
    protected $model = Personnel::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'position_id' => Position::factory(),
            'full_name' => fake()->name(),
            'contact_number' => '0917'.fake()->numerify('#######'),
            'email' => fake()->safeEmail(),
            'term_start' => now()->startOfYear(),
            'term_end' => null,
            'is_signatory' => true,
            'signature_path' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }
}
