<?php

namespace Database\Factories;

use App\Models\BarangayCase;
use App\Models\CaseNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseNote>
 */
class CaseNoteFactory extends Factory
{
    protected $model = CaseNote::class;

    public function definition(): array
    {
        return [
            'case_id' => BarangayCase::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
