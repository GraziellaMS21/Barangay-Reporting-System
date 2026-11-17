<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\InfoRequest;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ResolutionProof;
use App\Models\StatusLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResidentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $purok = Purok::first();
        $drainage = Category::where('slug', 'drainage')->first();
        $streetlight = Category::where('slug', 'streetlight')->first();
        $roadDamage = Category::where('slug', 'road-damage')->first();

        // 1. Report in 'needs_info' with a pending InfoRequest
        $reportInfo = Report::updateOrCreate(
            ['tracking_code' => 'BRG-INFO01'],
            [
                'description' => 'Streetlight near the basketball court keeps flickering on and off all night.',
                'report_type' => 'non_emergency',
                'category_id' => $streetlight?->id,
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
                'purok_id' => $purok?->id,
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

        if ($admin) {
            InfoRequest::firstOrCreate(
                ['report_id' => $reportInfo->id, 'question' => 'Which corner of the basketball court is this pole located? Nearest house number?'],
                [
                    'requested_by' => $admin->id,
                    'response' => null,
                    'responded_at' => null,
                ]
            );
        }

        // 2. Report in 'awaiting_confirmation' with ResolutionProof
        $reportAwaiting = Report::updateOrCreate(
            ['tracking_code' => 'BRG-CONF01'],
            [
                'description' => 'Large pothole in the middle of the street was causing vehicles to swerve dangerously.',
                'report_type' => 'non_emergency',
                'category_id' => $roadDamage?->id,
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
                'purok_id' => $purok?->id,
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

        if ($admin) {
            ResolutionProof::firstOrCreate(
                ['report_id' => $reportAwaiting->id],
                [
                    'user_id' => $admin->id,
                    'note' => 'Repairs completed on November 11. Asphalt leveled and rolled.',
                    'photo_path' => null,
                ]
            );
        }

        // 3. Report in 'resolved' with Feedback
        $reportResolved = Report::updateOrCreate(
            ['tracking_code' => 'BRG-RESL01'],
            [
                'description' => 'Drainage canal clogged with fallen branches and plastic bags.',
                'report_type' => 'non_emergency',
                'category_id' => $drainage?->id,
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
                'purok_id' => $purok?->id,
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
