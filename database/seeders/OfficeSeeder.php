<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $offices = [
            ['name' => 'Barangay Sanitation Office', 'type' => 'barangay committee', 'is_internal' => true, 'contact' => 'Barangay hall'],
            ['name' => 'Barangay Infrastructure Office', 'type' => 'barangay committee', 'is_internal' => true, 'contact' => 'Barangay hall'],
            ['name' => 'City Engineering Office', 'type' => 'city', 'is_internal' => false, 'contact' => 'city-engineering@example.test'],
            ['name' => 'City Health Office', 'type' => 'city', 'is_internal' => false, 'contact' => 'city-health@example.test'],
            ['name' => 'Public Utility Office', 'type' => 'utility', 'is_internal' => false, 'contact' => 'utility@example.test'],
        ];

        foreach ($offices as $office) {
            Office::firstOrCreate(
                ['name' => $office['name']],
                $office
            );
        }
    }
}
