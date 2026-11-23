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
            [
                'name' => 'Barangay Sanitation Office',
                'type' => 'internal',
                'description' => 'Handles solid waste collection, clogged canals, and sanitation concerns within Barangay Calarian.',
                'is_internal' => true,
                'is_external' => false,
                'contact' => 'Barangay Hall · Sanitation Committee',
                'contact_person' => null,
                'contact_phone' => null,
                'address' => 'Barangay Calarian Hall',
                'default_assignee_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Barangay Infrastructure Office',
                'type' => 'internal',
                'description' => 'Handles barangay footpaths, local drainage clearing, and minor community facility repairs.',
                'is_internal' => true,
                'is_external' => false,
                'contact' => 'Barangay Hall · Infrastructure Committee',
                'contact_person' => null,
                'contact_phone' => null,
                'address' => 'Barangay Calarian Hall',
                'default_assignee_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'City Engineering Office',
                'type' => 'external',
                'description' => 'Handles city-maintained roads, major drainage lines, and public infrastructure works.',
                'is_internal' => false,
                'is_external' => true,
                'contact' => null,
                'contact_person' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => 'City Engineers Office, Zamboanga City Hall',
                'is_active' => true,
            ],
            [
                'name' => 'City Health Office',
                'type' => 'external',
                'description' => 'Handles public health hazards, disease outbreaks, and sanitation inspections.',
                'is_internal' => false,
                'is_external' => true,
                'contact' => null,
                'contact_person' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => 'City Health Office, Pettit Barracks, Zamboanga City',
                'is_active' => true,
            ],
            [
                'name' => 'Public Utility Office',
                'type' => 'external',
                'description' => 'Handles streetlights, power poles, and public utility lines.',
                'is_internal' => false,
                'is_external' => true,
                'contact' => null,
                'contact_person' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => 'Public Utility Office, Don Pablo Lorenzo St., Zamboanga City',
                'is_active' => true,
            ],
            [
                'name' => 'City Social Welfare and Development Office (CSWDO)',
                'type' => 'external',
                'description' => 'Handles social welfare cases, child protection, family mediation, and social assistance.',
                'is_internal' => false,
                'is_external' => true,
                'contact' => null,
                'contact_person' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => 'City Hall Complex, Pettit Barracks, Zamboanga City',
                'is_active' => true,
            ],
        ];

        foreach ($offices as $office) {
            Office::firstOrCreate(['name' => $office['name']], $office);
        }
    }
}
