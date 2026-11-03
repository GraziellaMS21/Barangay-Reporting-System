<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Drainage', 'slug' => 'drainage', 'risk_weight' => 25],
            ['name' => 'Garbage', 'slug' => 'garbage', 'risk_weight' => 18],
            ['name' => 'Road Damage', 'slug' => 'road-damage', 'risk_weight' => 35],
            ['name' => 'Streetlight', 'slug' => 'streetlight', 'risk_weight' => 20],
            ['name' => 'Flooding', 'slug' => 'flooding', 'risk_weight' => 40],
            ['name' => 'Other', 'slug' => 'other', 'risk_weight' => 12],
        ] as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'risk_weight' => $category['risk_weight'], 'is_active' => true]
            );
        }
    }
}
