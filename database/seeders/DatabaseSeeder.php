<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's baseline database.
     * Demo accounts and scenarios are seeded explicitly via DemoDataSeeder.
     */
    public function run(): void
    {
        $this->call([
            RequiredDataSeeder::class,
        ]);
    }
}
