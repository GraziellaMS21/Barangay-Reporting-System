<?php

namespace Database\Factories;

use App\Models\Purok;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'tracking_code' => Report::generateTrackingCode(),
            'description' => fake()->paragraph(),
            'photo_path' => null,
            'lat' => fake()->latitude(6.90, 6.95),
            'lng' => fake()->longitude(122.05, 122.10),
            'purok_id' => Purok::factory(),
            'landmark' => fake()->words(3, true),
            'reporter_email' => fake()->safeEmail(),
            'status' => 'submitted',
        ];
    }
}
