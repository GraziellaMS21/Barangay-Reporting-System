<?php

namespace Database\Seeders;

use App\Models\ResponseTemplate;
use Illuminate\Database\Seeder;

class ResponseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['received', 'under review', 'needs more info', 'routed', 'resolved', 'out of scope'];
        foreach ($names as $n) {
            ResponseTemplate::create(['name' => $n, 'body' => 'Template: '.$n, 'is_active' => true]);
        }
    }
}
