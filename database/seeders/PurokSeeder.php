<?php

namespace Database\Seeders;

use App\Models\Purok;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurokSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7'] as $name) {
            Purok::firstOrCreate(
                ['name' => $name],
                ['code' => Str::slug($name), 'is_active' => true]
            );
        }
    }
}
