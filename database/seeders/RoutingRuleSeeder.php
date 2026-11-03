<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Office;
use App\Models\RoutingRule;
use Illuminate\Database\Seeder;

class RoutingRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $officeMap = [
            'drainage' => 'Barangay Sanitation Office',
            'garbage' => 'Barangay Sanitation Office',
            'road-damage' => 'City Engineering Office',
            'streetlight' => 'Public Utility Office',
            'flooding' => 'Barangay Infrastructure Office',
            'other' => 'Barangay Sanitation Office',
        ];

        foreach ($officeMap as $slug => $name) {
            $category = Category::query()->where('slug', $slug)->first();
            $office = Office::query()->where('name', $name)->first();

            if ($category && $office) {
                RoutingRule::firstOrCreate(
                    ['category_id' => $category->id],
                    [
                        'office_id' => $office->id,
                        'within_authority' => $slug !== 'road-damage',
                        'jurisdiction_note' => $slug === 'road-damage'
                            ? 'Road damage beyond barangay scope must be referred to the city engineering office.'
                            : 'Handled within barangay authority.',
                    ]
                );
            }
        }
    }
}
