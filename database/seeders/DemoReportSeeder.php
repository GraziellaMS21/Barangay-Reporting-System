<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\InfoRequest;
use App\Models\Office;
use App\Models\Purok;
use App\Models\Referral;
use App\Models\Report;
use App\Models\ResolutionProof;
use App\Models\StatusLog;
use App\Models\User;
use App\Services\ReferralService;
use App\Support\ReportStatus;
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
        $this->seedStaffScenarios($categoryIds, $purokId);
        $this->seedResidentScenarios($categoryIds, $purokId);
    }

    /**
     * Seed demo AI results covering review-page states.
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
     * Seed demo referrals in every tracking state.
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

        $staff = User::where('email', 'secretary@calarian.gov.ph')->first()
            ?? User::where('email', 'admin@calarian.gov.ph')->first()
            ?? User::whereIn('role', ['admin', 'staff'])->orderBy('id')->first();
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
                'letter' => "Dear City Engineering Office,\n\nWe would like to refer the road-damage issue reported under tracking code BRG-DEMO05 for your inspection and action.\n\nRespectfully,\nBarangay Calarian",
                'days_ago' => 4,
            ],
            [
                'tracking_code' => 'BRG-DEMO06',
                'description' => 'A large stagnant pool of dirty water near the market has become a mosquito breeding site.',
                'agency' => 'City Health Office',
                'status' => 'sent',
                'letter' => "Dear City Health Office,\n\nWe refer the sanitation/health hazard reported under tracking code BRG-DEMO06 for your guidance.\n\nRespectfully,\nBarangay Calarian",
                'days_ago' => 2,
            ],
            [
                'tracking_code' => 'BRG-DEMO07',
                'description' => 'Three lampposts along the highway boundary are leaning dangerously toward the sidewalk.',
                'agency' => 'Public Utility Office',
                'status' => 'acknowledged',
                'letter' => "Dear Public Utility Office,\n\nWe refer leaning lampposts along the highway under tracking code BRG-DEMO07.\n\nRespectfully,\nBarangay Calarian",
                'days_ago' => 5,
                'updates' => [
                    ['note' => 'Referral acknowledged. Inspection team scheduled for Thursday.', 'days_ago' => 3],
                ],
            ],
            [
                'tracking_code' => 'BRG-DEMO08',
                'description' => 'A damaged culvert at the boundary between Calarian and San Roque causes backflow during heavy downpours.',
                'agency' => 'City Engineering Office',
                'status' => 'sent',
                'letter' => "Dear City Engineering Office,\n\nUrgent referral regarding boundary culvert backflow under tracking code BRG-DEMO08.\n\nRespectfully,\nBarangay Calarian",
                'days_ago' => 10,
                'updates' => [
                    ['note' => 'Automated reminder: Follow-up is now overdue (7+ days elapsed without update).', 'days_ago' => 3],
                ],
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
                    'staff_description' => $demoReferral['description'],
                    'category_id' => $categoryId,
                    'purok_id' => $purokId,
                    'status' => 'routed',
                    'urgency' => 'High',
                    'reviewed_by' => $staff?->id,
                    'reviewed_at' => now()->subDays($demoReferral['days_ago']),
                ]
            );

            if (! $report->referrals()->exists()) {
                $referral = Referral::create([
                    'report_id' => $report->id,
                    'office_id' => $agencyId,
                    'authority_mode' => 'beyond',
                    'status' => $demoReferral['status'],
                    'referral_letter' => $demoReferral['letter'],
                    'referred_by' => $staff?->id,
                    'follow_up_owner_id' => $staff?->id,
                    'sent_at' => in_array($demoReferral['status'], ['sent', 'acknowledged'], true)
                        ? now()->subDays($demoReferral['days_ago'])
                        : null,
                    'follow_up_at' => $referralService->calculateFollowUpDate(now()->subDays($demoReferral['days_ago'])),
                    'follow_up_notes' => $demoReferral['status'] === 'sent' && $demoReferral['days_ago'] >= 7
                        ? 'Follow-up overdue per 7-day threshold.'
                        : null,
                ]);

                foreach ($demoReferral['updates'] ?? [] as $update) {
                    $referral->updates()->create([
                        'user_id' => $staff?->id,
                        'message' => $update['note'],
                        'occurred_at' => now()->subDays($update['days_ago']),
                        'visible_to_resident' => true,
                    ]);
                }

                $referral->update(['status' => $demoReferral['status']]);
            }
        }
    }

    /**
     * Seed internal routed demo reports.
     */
    private function seedInternalRoutedReports($categoryIds): void
    {
        $internalOffices = Office::query()
            ->where('is_internal', true)
            ->pluck('id', 'name');
        if ($internalOffices->isEmpty()) {
            return;
        }

        $staff = User::where('email', 'secretary@calarian.gov.ph')->first()
            ?? User::where('email', 'admin@calarian.gov.ph')->first()
            ?? User::whereIn('role', ['staff', 'admin'])->orderBy('id')->first();
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

    /**
     * Merged scenarios from StaffSeeder.
     */
    private function seedStaffScenarios($categoryIds, $purokId): void
    {
        $actor = User::where('email', 'secretary@calarian.gov.ph')->first()
            ?? User::where('email', 'admin@calarian.gov.ph')->first()
            ?? User::first();
        $staff = User::where('email', 'staff@calarian.gov.ph')->first() ?? $actor;
        $admin = User::where('email', 'admin@calarian.gov.ph')->first() ?? $actor;

        // 1. BRG-STF001: Assigned in-progress case with internal notes
        $reportStf1 = Report::firstOrCreate(
            ['tracking_code' => 'BRG-STF001'],
            [
                'description' => 'Blocked drainage causing flood along main access road.',
                'staff_description' => 'Drainage canal obstructed by debris.',
                'purok_id' => $purokId,
                'category_id' => $categoryIds->get('drainage') ?? $categoryIds->first(),
                'status' => ReportStatus::IN_PROGRESS,
                'urgency' => 'High',
                'assigned_to' => $staff?->id,
                'due_at' => now()->addDays(2),
                'reporter_name' => 'Maria Santos',
                'reporter_contact' => '09171234567',
                'reporter_email' => 'maria@example.com',
                'lat' => 6.9214,
                'lng' => 122.0790,
            ]
        );

        if ($staff) {
            $reportStf1->internalNotes()->firstOrCreate(
                ['body' => 'Spoke with engineering maintenance team. Work order scheduled for Friday morning.'],
                ['user_id' => $staff->id]
            );
        }

        // 2. BRG-STF002: Report with "Needs Info" request
        $reportStf2 = Report::firstOrCreate(
            ['tracking_code' => 'BRG-STF002'],
            [
                'description' => 'Streetlight flickering and turning off intermittently near the chapel.',
                'staff_description' => 'Intermittent streetlight failure.',
                'purok_id' => $purokId,
                'category_id' => $categoryIds->get('streetlight') ?? $categoryIds->first(),
                'status' => ReportStatus::NEEDS_INFO,
                'urgency' => 'Medium',
                'assigned_to' => $staff?->id,
                'due_at' => now()->addDays(1),
                'reporter_name' => 'Juan Dela Cruz',
                'reporter_contact' => '09189876543',
                'lat' => 6.9220,
                'lng' => 122.0795,
            ]
        );

        if ($staff) {
            $reportStf2->infoRequests()->firstOrCreate(
                ['question' => 'Could you provide the exact pole number or the nearest house landmark?'],
                [
                    'requested_by' => $staff->id,
                    'response' => 'It is the concrete post right in front of house #42, beside the mango tree.',
                    'responded_at' => now()->subHours(2),
                ]
            );
        }

        // 3. BRG-OVD001: Overdue active report
        Report::firstOrCreate(
            ['tracking_code' => 'BRG-OVD001'],
            [
                'description' => 'Uncollected garbage accumulating near public market stall.',
                'staff_description' => 'Solid waste accumulation.',
                'purok_id' => $purokId,
                'category_id' => $categoryIds->get('garbage') ?? $categoryIds->first(),
                'status' => ReportStatus::UNDER_REVIEW,
                'urgency' => 'High',
                'assigned_to' => $staff?->id,
                'due_at' => now()->subDay(),
                'lat' => 6.9230,
                'lng' => 122.0800,
            ]
        );

        // 4. BRG-RES001: Resolution proposed (awaiting confirmation)
        $reportRes = Report::firstOrCreate(
            ['tracking_code' => 'BRG-RES001'],
            [
                'description' => 'Fallen tree branch obstructing foot traffic on sidewalk.',
                'staff_description' => 'Tree branch obstacle cleared.',
                'purok_id' => $purokId,
                'category_id' => $categoryIds->get('road-damage') ?? $categoryIds->get('other') ?? $categoryIds->first(),
                'status' => ReportStatus::AWAITING_CONFIRMATION,
                'urgency' => 'Medium',
                'assigned_to' => $staff?->id,
                'due_at' => now()->addDays(1),
                'lat' => 6.9240,
                'lng' => 122.0810,
            ]
        );

        if ($staff) {
            $reportRes->resolutionProofs()->firstOrCreate(
                ['note' => 'Barangay cleanup crew cleared and disposed of the fallen tree branches. Walkway is now clear.'],
                ['user_id' => $staff->id]
            );
        }

        // 5. BRG-REO001: Reopened case
        Report::firstOrCreate(
            ['tracking_code' => 'BRG-REO001'],
            [
                'description' => 'Pothole temporarily covered with gravel washed away after rain.',
                'staff_description' => 'Pothole reappeared following downpour.',
                'purok_id' => $purokId,
                'category_id' => $categoryIds->get('road-damage') ?? $categoryIds->first(),
                'status' => ReportStatus::REOPENED,
                'reopened_count' => 1,
                'urgency' => 'High',
                'assigned_to' => $staff?->id,
                'due_at' => now()->addHours(12),
                'lat' => 6.9250,
                'lng' => 122.0820,
            ]
        );

        // 6. BRG-ESC001: Escalated case
        Report::firstOrCreate(
            ['tracking_code' => 'BRG-ESC001'],
            [
                'description' => 'Exposed low-hanging electrical wire near basketball court.',
                'staff_description' => 'Hazardous electrical wire.',
                'purok_id' => $purokId,
                'category_id' => $categoryIds->get('other') ?? $categoryIds->first(),
                'status' => ReportStatus::IN_PROGRESS,
                'urgency' => 'Critical',
                'assigned_to' => $admin?->id ?? $staff?->id,
                'due_at' => now()->subHours(6),
                'escalated_at' => now()->subHours(1),
                'lat' => 6.9260,
                'lng' => 122.0830,
            ]
        );
    }

    /**
     * Merged scenarios from ResidentSeeder.
     */
    private function seedResidentScenarios($categoryIds, $purokId): void
    {
        $actor = User::where('email', 'secretary@calarian.gov.ph')->first()
            ?? User::where('email', 'admin@calarian.gov.ph')->first()
            ?? User::first();

        // 1. BRG-INFO01: Report in 'needs_info' with a pending InfoRequest
        $reportInfo = Report::updateOrCreate(
            ['tracking_code' => 'BRG-INFO01'],
            [
                'description' => 'Streetlight near the basketball court keeps flickering on and off all night.',
                'report_type' => 'non_emergency',
                'category_id' => $categoryIds->get('streetlight') ?? $categoryIds->first(),
                'answers' => [
                    'not_working' => true,
                    'pole_number' => 'Pole #14',
                ],
                'is_ongoing' => true,
                'is_recurring' => true,
                'people_at_risk' => false,
                'is_anonymous' => false,
                'preferred_contact' => 'phone',
                'reporter_name' => 'Maria Makiling',
                'reporter_contact' => '09171234567',
                'consent_at' => now()->subDays(2),
                'purok_id' => $purokId,
                'landmark' => 'Beside basketball court',
                'status' => 'needs_info',
                'lat' => 6.9218,
                'lng' => 122.0805,
            ]
        );

        StatusLog::firstOrCreate(
            ['report_id' => $reportInfo->id, 'to_status' => 'submitted'],
            ['note' => 'Report received.', 'created_at' => now()->subDays(2)]
        );
        StatusLog::firstOrCreate(
            ['report_id' => $reportInfo->id, 'to_status' => 'needs_info'],
            ['note' => 'Please provide the nearest house number or landmark.', 'created_at' => now()->subDay()]
        );

        if ($actor) {
            InfoRequest::firstOrCreate(
                ['report_id' => $reportInfo->id, 'question' => 'Which corner of the basketball court is this pole located? Nearest house number?'],
                [
                    'requested_by' => $actor->id,
                    'response' => null,
                    'responded_at' => null,
                ]
            );
        }

        // 2. BRG-CONF01: Report in 'awaiting_confirmation' with ResolutionProof
        $reportAwaiting = Report::updateOrCreate(
            ['tracking_code' => 'BRG-CONF01'],
            [
                'description' => 'Large pothole in the middle of the street was causing vehicles to swerve dangerously.',
                'report_type' => 'non_emergency',
                'category_id' => $categoryIds->get('road-damage') ?? $categoryIds->first(),
                'answers' => [
                    'damage_type' => 'Pothole',
                    'size' => 'About 1 meter wide',
                ],
                'is_ongoing' => false,
                'is_recurring' => false,
                'people_at_risk' => true,
                'is_anonymous' => false,
                'preferred_contact' => 'email',
                'reporter_name' => 'Jose Rizal',
                'reporter_email' => 'jose@example.test',
                'consent_at' => now()->subDays(3),
                'purok_id' => $purokId,
                'landmark' => 'In front of elementary school',
                'status' => 'awaiting_confirmation',
                'lat' => 6.9230,
                'lng' => 122.0815,
            ]
        );

        StatusLog::firstOrCreate(
            ['report_id' => $reportAwaiting->id, 'to_status' => 'submitted'],
            ['note' => 'Report received.', 'created_at' => now()->subDays(3)]
        );
        StatusLog::firstOrCreate(
            ['report_id' => $reportAwaiting->id, 'to_status' => 'in_progress'],
            ['note' => 'Maintenance team dispatched.', 'created_at' => now()->subDays(2)]
        );
        StatusLog::firstOrCreate(
            ['report_id' => $reportAwaiting->id, 'to_status' => 'awaiting_confirmation'],
            ['note' => 'Pothole patched with asphalt. Awaiting resident confirmation.', 'created_at' => now()->subHours(6)]
        );

        if ($actor) {
            ResolutionProof::firstOrCreate(
                ['report_id' => $reportAwaiting->id],
                [
                    'user_id' => $actor->id,
                    'note' => 'Repairs completed on November 11. Asphalt leveled and rolled.',
                    'photo_path' => null,
                ]
            );
        }

        // 3. BRG-RESL01: Report in 'resolved' with Feedback
        $reportResolved = Report::updateOrCreate(
            ['tracking_code' => 'BRG-RESL01'],
            [
                'description' => 'Drainage canal clogged with fallen branches and plastic bags.',
                'report_type' => 'non_emergency',
                'category_id' => $categoryIds->get('drainage') ?? $categoryIds->first(),
                'answers' => [
                    'water_level' => 'Knee',
                    'blocked_drain' => true,
                ],
                'is_ongoing' => false,
                'is_recurring' => true,
                'people_at_risk' => false,
                'is_anonymous' => true,
                'preferred_contact' => 'none',
                'consent_at' => now()->subDays(5),
                'created_at' => now()->subDays(2),
                'purok_id' => $purokId,
                'landmark' => 'Canal behind daycare',
                'status' => 'resolved',
                'resolved_at' => now()->subDay(),
                'resolution_confirmed_at' => now()->subDay(),
                'lat' => 6.9205,
                'lng' => 122.0790,
            ]
        );

        Feedback::firstOrCreate(
            ['report_id' => $reportResolved->id],
            [
                'rating' => 5,
                'comment' => 'Very fast response from the barangay team! The canal is clean now.',
            ]
        );
    }
}
