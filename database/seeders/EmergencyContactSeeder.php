<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ['name' => 'Police Station (replace)', 'number' => 'ADD NUMBER', 'type' => 'police'],
            ['name' => 'Fire Station (replace)', 'number' => 'ADD NUMBER', 'type' => 'fire'],
            ['name' => 'Health Office / Ambulance (replace)', 'number' => 'ADD NUMBER', 'type' => 'health'],
            ['name' => 'Disaster Response Office (replace)', 'number' => 'ADD NUMBER', 'type' => 'disaster'],
            ['name' => 'Barangay Hotline (replace)', 'number' => 'ADD NUMBER', 'type' => 'barangay'],
        ];
        foreach ($contacts as $c) {
            \App\Models\EmergencyContact::firstOrCreate(['name' => $c['name']], $c);
        }
    }
}
