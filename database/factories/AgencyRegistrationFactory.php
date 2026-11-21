<?php

namespace Database\Factories;

use App\Models\AgencyRegistration;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgencyRegistration>
 */
class AgencyRegistrationFactory extends Factory
{
    protected $model = AgencyRegistration::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'agency']),
            'office_id' => Office::first()?->id ?? null,
            'agency_name_claimed' => fake()->company(),
            'position_title' => 'Liaison Officer',
            'contact_number' => '0918'.fake()->numerify('#######'),
            'id_document_path' => 'agency_ids/'.fake()->uuid().'.jpg',
            'status' => 'pending',
            'is_office_admin' => false,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'decision_note' => null,
        ];
    }
}
