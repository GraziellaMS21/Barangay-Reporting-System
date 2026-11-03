<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\PriorityService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PriorityService::DEFAULT_WEIGHTS as $key => $value) {
            Setting::updateOrCreate(['key' => 'priority.'.$key], ['value' => $value]);
        }
    }
}
