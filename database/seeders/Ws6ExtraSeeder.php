<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class Ws6ExtraSeeder extends Seeder
{
    public function run(): void
    {
        Permission::firstOrCreate(
            ['slug' => 'imports.manage'],
            [
                'label' => 'Manage Legacy File Imports',
                'group' => 'imports',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'records.lookback_years'],
            [
                'value' => '4',
                'type' => 'integer',
                'group' => 'fiscal_year',
                'label' => 'Historical Record Lookback Years',
                'description' => 'Number of past record years staff search and import from legacy files.',
                'source_question' => 'Q8',
                'is_confirmed' => false,
            ]
        );
    }
}
