<?php

namespace Database\Seeders;

use App\Models\EmergencyContact;
use Illuminate\Database\Seeder;

class EmergencyContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contacts = [
            ['name' => 'National Emergency Hotline', 'number' => '911', 'type' => 'emergency'],
            ['name' => 'Police Station', 'number' => 'N/A', 'type' => 'police'],
            ['name' => 'Fire Station', 'number' => 'N/A', 'type' => 'fire'],
            ['name' => 'Health Office / Ambulance', 'number' => 'N/A', 'type' => 'health'],
            ['name' => 'Disaster Response Office', 'number' => 'N/A', 'type' => 'disaster'],
            ['name' => 'Barangay Hotline', 'number' => 'N/A', 'type' => 'barangay'],
        ];
        foreach ($contacts as $contact) {
            EmergencyContact::updateOrCreate(['type' => $contact['type']], $contact);
        }
    }
}
