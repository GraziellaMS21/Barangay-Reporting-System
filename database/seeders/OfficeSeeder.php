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
            ['name' => 'Barangay Sanitation Office', 'type' => 'barangay committee', 'is_internal' => true, 'is_external' => false, 'contact' => 'Barangay hall'],
            ['name' => 'Barangay Infrastructure Office', 'type' => 'barangay committee', 'is_internal' => true, 'is_external' => false, 'contact' => 'Barangay hall'],
            [
                'name' => 'City Engineering Office',
                'type' => 'city',
                'is_internal' => false,
                'is_external' => true,
                'contact' => 'city-engineering@example.test',
                'contact_person' => 'Engr. Ana Reyes',
                'contact_email' => 'city-engineering@example.test',
                'contact_phone' => '(062) 991-0001',
                'address' => 'City Engineers Office, Zamboanga City Hall',
            ],
            [
                'name' => 'City Health Office',
                'type' => 'city',
                'is_internal' => false,
                'is_external' => true,
                'contact' => 'city-health@example.test',
                'contact_person' => 'Dr. Miguel Lopez',
                'contact_email' => 'city-health@example.test',
                'contact_phone' => '(062) 991-0002',
                'address' => 'City Health Office, Pettit Barracks, Zamboanga City',
            ],
            [
                'name' => 'Public Utility Office',
                'type' => 'utility',
                'is_internal' => false,
                'is_external' => true,
                'contact' => 'utility@example.test',
                'contact_person' => 'Ms. Rosa Lim',
                'contact_email' => 'utility@example.test',
                'contact_phone' => '(062) 991-0003',
                'address' => 'Public Utility Office, Don Pablo Lorenzo St., Zamboanga City',
            ],
        ];

        foreach ($offices as $office) {
            $existing = Office::query()->firstWhere('name', $office['name']);
            if ($existing) {
                $existing->fill($office)->save();

                continue;
            }

            Office::query()->create($office);
        }
    }
}
