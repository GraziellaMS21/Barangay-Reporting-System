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

        $this->seedAiResults($categoryIds);
    }

    /**
     * Seed demo AI results covering the four review-page states:
     * ready (high confidence), low confidence, pending, and failed.
     */
    private function seedAiResults($categoryIds): void
    {
        $demo = Report::query()->whereIn('tracking_code', ['BRG-DEMO01', 'BRG-DEMO02', 'BRG-DEMO03', 'BRG-DEMO04'])->get()->keyBy('tracking_code');

        $ready = $demo->get('BRG-DEMO01');
        if ($ready && ! $ready->aiResult()->exists()) {
            $ready->aiResult()->create([
                'suggested_category' => 'drainage',
                'suggested_urgency' => 'medium',
                'summary' => 'A clogged drainage canal near the community hall accumulates standing water during heavy rainfall, posing a flood and dengue risk to nearby households.',
                'photo_mismatch' => false,
                'emergency_flag' => false,
                'key_facts' => ['Clogged drainage', 'Standing water', 'Near community hall'],
                'confidence' => 0.92,
                'model' => 'demo-seeder',
            ]);
            $ready->forceFill(['ai_status' => 'done'])->save();
        }

        $lowConfidence = $demo->get('BRG-DEMO02');
        if ($lowConfidence && ! $lowConfidence->aiResult()->exists()) {
            $lowConfidence->aiResult()->create([
                'suggested_category' => 'garbage',
                'suggested_urgency' => 'low',
                'summary' => 'Uncollected garbage has accumulated beside the covered court and may attract pests if not collected soon.',
                'photo_mismatch' => true,
                'emergency_flag' => false,
                'key_facts' => ['Uncollected garbage', 'Beside covered court'],
                'confidence' => 0.45,
                'model' => 'demo-seeder',
            ]);
            $lowConfidence->forceFill(['ai_status' => 'done'])->save();
        }

        $pending = $demo->get('BRG-DEMO03');
        if ($pending) {
            $pending->forceFill(['ai_status' => 'pending'])->save();
        }

        $failed = $demo->get('BRG-DEMO04');
        if ($failed) {
            $failed->aiResult()->firstOrCreate([], [
                'error' => 'AI provider request failed (demo data).',
                'model' => 'demo-seeder',
            ]);
            $failed->forceFill(['ai_status' => 'failed'])->save();
        }
    }
}
