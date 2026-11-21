<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PurokSeeder::class,
            EmergencyContactSeeder::class,
            CategorySeeder::class,
            OfficeSeeder::class,
            RoutingRuleSeeder::class,
            SettingSeeder::class,
            RequiredDataSeeder::class,
            UserSeeder::class,
            DemoReportSeeder::class,
            CategoryQuestionSeeder::class,
            ResponseTemplateSeeder::class,
            ResidentSeeder::class,
            StaffSeeder::class,
            EngineSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
