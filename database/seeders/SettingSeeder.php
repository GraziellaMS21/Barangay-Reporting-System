<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'priority.urgency' => 20,
            'priority.category' => 10,
            'priority.sensitive_place' => 25,
            'priority.supporting_reports' => 8,
            'priority.age_days' => 2,
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
