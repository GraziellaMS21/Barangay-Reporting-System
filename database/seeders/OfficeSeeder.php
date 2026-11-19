<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffUserId = User::query()
            ->where('role', 'staff')
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        $offices = [
            [
                'name' => 'Barangay Sanitation Office',
                'type' => 'internal',
                'description' => 'Handles solid waste collection, clogged canals, and sanitation concerns within Barangay Calarian.',
                'is_internal' => true,
                'is_external' => false,
                'contact' => 'Barangay Hall · Sanitation Committee',
                'contact_person' => 'Kgd. Ramon Bautista',
                'contact_phone' => '(062) 991-1001',
                'address' => 'Barangay Calarian Hall',
                'default_assignee_id' => $staffUserId,
                'is_active' => true,
            ],
            [
                'name' => 'Barangay Infrastructure Office',
                'type' => 'internal',
                'description' => 'Handles barangay footpaths, local drainage clearing, and minor community facility repairs.',
                'is_internal' => true,
                'is_external' => false,
                'contact' => 'Barangay Hall · Infrastructure Committee',
                'contact_person' => 'Kgd. Elena Mendoza',
                'contact_phone' => '(062) 991-1002',
                'address' => 'Barangay Calarian Hall',
                'default_assignee_id' => $staffUserId,
                'is_active' => true,
            ],
            [
                'name' => 'City Engineering Office',
                'type' => 'external',
                'description' => 'Handles city-maintained roads, major drainage lines, and public infrastructure works.',
                'is_internal' => false,
                'is_external' => true,
                'contact' => 'city-engineering@example.test',
                'contact_person' => 'Engr. Ana Reyes',
                'contact_email' => 'city-engineering@example.test',
                'contact_phone' => '(062) 991-0001',
                'address' => 'City Engineers Office, Zamboanga City Hall',
                'is_active' => true,
            ],
            [
                'name' => 'City Health Office',
                'type' => 'external',
                'description' => 'Handles public health hazards, disease outbreaks, and sanitation inspections.',
                'is_internal' => false,
                'is_external' => true,
                'contact' => 'city-health@example.test',
                'contact_person' => 'Dr. Miguel Lopez',
                'contact_email' => 'city-health@example.test',
                'contact_phone' => '(062) 991-0002',
                'address' => 'City Health Office, Pettit Barracks, Zamboanga City',
                'is_active' => true,
            ],
            [
                'name' => 'Public Utility Office',
                'type' => 'external',
                'description' => 'Handles streetlights, power poles, and public utility lines.',
                'is_internal' => false,
                'is_external' => true,
                'contact' => 'utility@example.test',
                'contact_person' => 'Ms. Rosa Lim',
                'contact_email' => 'utility@example.test',
                'contact_phone' => '(062) 991-0003',
                'address' => 'Public Utility Office, Don Pablo Lorenzo St., Zamboanga City',
                'is_active' => true,
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
