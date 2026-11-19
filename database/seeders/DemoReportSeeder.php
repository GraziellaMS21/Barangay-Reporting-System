<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Office;
use App\Models\Purok;
use App\Models\Referral;
use App\Models\Report;
use App\Models\User;
use App\Services\ReferralService;
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
        $this->seedReferrals();
        $this->seedInternalRoutedReports($categoryIds);
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

    /**
     * Seed demo referrals in every tracking state: prepared, sent,
     * acknowledged, and an overdue follow-up.
     */
    private function seedReferrals(): void
    {
        $agencyIds = Office::query()
            ->where('is_external', true)
            ->orderBy('id')
            ->pluck('id', 'name');
        if ($agencyIds->isEmpty()) {
            return;
        }

        $staff = User::query()->whereIn('role', ['admin', 'staff'])->orderBy('id')->first();
        $purokId = Purok::query()->orderBy('id')->value('id');
        $categoryId = Category::query()->where('slug', 'road-damage')->value('id')
            ?? Category::query()->orderBy('id')->value('id');
        $referralService = app(ReferralService::class);

        $demoReferrals = [
            [
                'tracking_code' => 'BRG-DEMO05',
                'description' => 'The main road near the barangay hall has deep cracks and potholes that need city-level repair.',
                'agency' => 'City Engineering Office',
                'status' => 'prepared',
            ],
            [
                'tracking_code' => 'BRG-DEMO06',
                'description' => 'Streetlights along the road near the public market are not working; the city installs and maintains them.',
                'agency' => 'Public Utility Office',
                'status' => 'sent',
                'sent_days_ago' => 3,
                'updates' => [
                    ['days_ago' => 1, 'note' => 'Called the agency duty desk; they received the referral and assigned an inspector.', 'channel' => 'phone'],
                ],
            ],
            [
                'tracking_code' => 'BRG-DEMO07',
                'description' => 'Standing floodwater near the public school appears to come from a blocked city drainage line.',
                'agency' => 'City Engineering Office',
                'status' => 'acknowledged',
                'sent_days_ago' => 9,
                'updates' => [
                    ['days_ago' => 7, 'note' => 'The agency replied by email acknowledging receipt of the referral.', 'channel' => 'email'],
                    ['days_ago' => 2, 'note' => 'Site visit scheduled; the agency crew will clear the drainage line this week.', 'channel' => 'site_visit'],
                ],
            ],
            [
                'tracking_code' => 'BRG-DEMO08',
                'description' => 'A leaning utility pole beside the footbridge needs replacement by the utility company.',
                'agency' => 'Public Utility Office',
                'status' => 'overdue',
                'sent_days_ago' => 12,
                'updates' => [],
            ],
        ];

        foreach ($demoReferrals as $demoReferral) {
            $agencyId = $agencyIds->get($demoReferral['agency']);
            if (! $agencyId) {
                continue;
            }

            $report = Report::firstOrCreate(
                ['tracking_code' => $demoReferral['tracking_code']],
                [
                    'description' => $demoReferral['description'],
                    'category_id' => $categoryId,
                    'purok_id' => $purokId,
                    'status' => 'referred',
                    'urgency' => 'Medium',
                    'staff_description' => $demoReferral['description'],
                    'office_id' => $agencyId,
                ]
            );

            $sentAt = isset($demoReferral['sent_days_ago'])
                ? now()->subDays($demoReferral['sent_days_ago'])
                : null;
            $status = $demoReferral['status'] === 'overdue' ? 'sent' : $demoReferral['status'];

            $referral = Referral::firstOrCreate(
                ['report_id' => $report->id, 'office_id' => $agencyId],
                [
                    'reference_no' => $referralService->generateReferenceNo(),
                    'referral_text' => $referralService->buildLetter($report, Office::find($agencyId)),
                    'status' => $status,
                    'sent_at' => $sentAt,
                    'sent_via' => $sentAt ? 'email' : null,
                    'follow_up_at' => $sentAt?->copy()->addDays(
                        $demoReferral['status'] === 'overdue' ? 9 : 4
                    ),
                    'prepared_by' => $staff?->id,
                    'follow_up_owner_id' => $staff?->id,
                ]
            );

            if ($referral->wasRecentlyCreated && ($demoReferral['updates'] ?? []) !== []) {
                foreach ($demoReferral['updates'] as $update) {
                    $referral->updates()->create([
                        'user_id' => $staff?->id,
                        'note' => $update['note'],
                        'channel' => $update['channel'],
                        'occurred_at' => now()->subDays($update['days_ago']),
                        'visible_to_resident' => true,
                    ]);
                }

                $referral->update(['status' => $demoReferral['status']]);
            }
        }
    }

    private function seedInternalRoutedReports($categoryIds): void
    {
        $internalOffices = Office::query()
            ->where('is_internal', true)
            ->pluck('id', 'name');
        if ($internalOffices->isEmpty()) {
            return;
        }

        $staff = User::query()->whereIn('role', ['staff', 'admin'])->orderBy('id')->first();
        $purokId = Purok::query()->orderBy('id')->value('id');

        $demoInternal = [
            [
                'tracking_code' => 'BRG-DEMO09',
                'description' => 'Overflowing garbage bins along the alley near Purok 1 chapel need scheduled pickup.',
                'category' => 'garbage',
                'office' => 'Barangay Sanitation Office',
                'status' => 'routed',
                'urgency' => 'High',
                'due_at' => now()->addDays(2),
            ],
            [
                'tracking_code' => 'BRG-DEMO10',
                'description' => 'Silt and debris blocking the barangay footpath canal after last night’s rain.',
                'category' => 'drainage',
                'office' => 'Barangay Infrastructure Office',
                'status' => 'in_progress',
                'urgency' => 'Medium',
                'due_at' => now()->subDay(),
            ],
        ];

        foreach ($demoInternal as $item) {
            $officeId = $internalOffices->get($item['office']);
            if (! $officeId) {
                continue;
            }

            $report = Report::firstOrCreate(
                ['tracking_code' => $item['tracking_code']],
                [
                    'description' => $item['description'],
                    'staff_description' => $item['description'],
                    'category_id' => $categoryIds->get($item['category']) ?? $categoryIds->first(),
                    'purok_id' => $purokId,
                    'office_id' => $officeId,
                    'assigned_to' => $staff?->id,
                    'reviewed_by' => $staff?->id,
                    'reviewed_at' => now()->subDays(2),
                    'status' => $item['status'],
                    'urgency' => $item['urgency'],
                    'due_at' => $item['due_at'],
                ]
            );

            if ($report->wasRecentlyCreated) {
                $report->statusLogs()->create([
                    'user_id' => $staff?->id,
                    'office_id' => $officeId,
                    'from_status' => 'under_review',
                    'to_status' => 'routed',
                    'note' => 'Routed to '.$item['office'].'.',
                ]);
            }
        }
    }
}
