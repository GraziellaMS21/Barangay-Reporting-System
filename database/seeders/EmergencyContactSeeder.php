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
            ['name' => 'National Emergency Hotline', 'number' => '911', 'type' => 'emergency', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Police Station', 'number' => 'N/A', 'type' => 'police', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Fire Station', 'number' => 'N/A', 'type' => 'fire', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Health Office / Ambulance', 'number' => 'N/A', 'type' => 'health', 'is_active' => true, 'sort_order' => 4],
            ['name' => 'Disaster Response Office', 'number' => 'N/A', 'type' => 'disaster', 'is_active' => true, 'sort_order' => 5],
            ['name' => 'Barangay Hotline', 'number' => 'N/A', 'type' => 'barangay', 'is_active' => true, 'sort_order' => 6],
        ];
        foreach ($contacts as $contact) {
            EmergencyContact::updateOrCreate([
                'name' => $contact['name'],
                'type' => $contact['type'],
            ], $contact);
        }
    }
}
