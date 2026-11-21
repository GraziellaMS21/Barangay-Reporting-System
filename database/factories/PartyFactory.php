<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\Purok;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    protected $model = Party::class;

    public function definition(): array
    {
        $filipinoLastNames = ['Santos', 'dela Cruz', 'Reyes', 'Ramos', 'Garcia', 'Mendoza', 'Flores', 'Gonzales', 'Bautista', 'Villanueva'];
        $filipinoFirstNames = ['Juan', 'Maria', 'Jose', 'Antonio', 'Rodrigo', 'Elena', 'Carmen', 'Grace', 'Danilo', 'Luzviminda'];

        $name = fake()->randomElement($filipinoFirstNames).' '.fake()->randomElement($filipinoLastNames);

        return [
            'full_name' => $name,
            'alias' => fake()->boolean(30) ? fake()->firstName() : null,
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'sex' => fake()->randomElement(['Male', 'Female']),
            'civil_status' => fake()->randomElement(['Single', 'Married', 'Widowed', 'Separated']),
            'occupation' => fake()->jobTitle(),
            'contact_number' => '0917'.fake()->numerify('#######'),
            'email' => fake()->safeEmail(),
            'address_line' => fake()->streetAddress().', Calarian',
            'purok_id' => Purok::first()?->id ?? null,
            'home_barangay' => 'Calarian',
            'home_city' => 'Zamboanga City',
            'residency_status' => 'resident',
            'residency_basis' => 'voter_registration',
            'residency_verified_by' => null,
            'residency_verified_at' => now(),
            'id_type' => 'Barangay ID',
            'id_number' => 'CAL-'.fake()->numerify('####-####'),
            'extra' => null,
        ];
    }
}
