<?php

namespace Database\Factories;

use App\Models\AgencyInvitation;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgencyInvitation>
 */
class AgencyInvitationFactory extends Factory
{
    protected $model = AgencyInvitation::class;

    public function definition(): array
    {
        return [
            'office_id' => Office::factory(),
            'email' => fake()->safeEmail(),
            'token_hash' => hash('sha256', fake()->uuid()),
            'invited_by' => User::factory(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }
}
