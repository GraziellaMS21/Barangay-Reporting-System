<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Purok;
use App\Models\Report;
use Illuminate\Database\Seeder;

class DemoReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purokId = Purok::query()->orderBy('id')->value('id');
        $categoryIds = Category::query()->pluck('id', 'slug');

        foreach ([
            [
                'tracking_code' => 'BRG-DEMO01',
                'description' => 'A clogged drainage near the community hall causes water to pool during heavy rain.',
                'category' => 'drainage',
                'reporter_name' => 'Juan Dela Cruz',
                'reporter_contact' => '09170000001',
            ],
            [
                'tracking_code' => 'BRG-DEMO02',
                'description' => 'Garbage has been piling up beside the covered court.',
                'category' => 'garbage',
                'reporter_name' => 'Maria Santos',
                'reporter_contact' => null,
            ],
            [
                'tracking_code' => 'BRG-DEMO03',
                'description' => 'A streetlight near the corner has stopped working.',
                'category' => 'streetlight',
                'reporter_name' => null,
                'reporter_contact' => '+639170000003',
            ],
            [
                'tracking_code' => 'BRG-DEMO04',
                'description' => 'The road surface is damaged near the public waiting shed.',
                'category' => 'road-damage',
                'reporter_name' => null,
                'reporter_contact' => null,
            ],
        ] as $report) {
            $categoryId = $categoryIds->get($report['category']);
            unset($report['category']);

            Report::firstOrCreate(
                ['tracking_code' => $report['tracking_code']],
                [
                    ...$report,
                    'category_id' => $categoryId,
                    'purok_id' => $purokId,
                    'status' => 'submitted',
                ]
            );
        }
    }
}
