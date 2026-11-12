<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use App\Support\ReportStatus;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staff = User::where('email', 'staff@barangay.test')->first();
        $admin = User::where('email', 'admin@barangay.test')->first();

        if (! $staff) {
            return;
        }

        $purok = Purok::first();
        $category = Category::first();

        // 1. Assigned in-progress case with internal notes
        $assignedReport = Report::firstOrCreate(
            ['tracking_code' => 'BRG-STF001'],
            [
                'description' => 'Blocked drainage causing flood along main access road.',
                'staff_description' => 'Drainage canal obstructed by debris.',
                'purok_id' => $purok?->id,
                'category_id' => $category?->id,
                'status' => ReportStatus::IN_PROGRESS,
                'urgency' => 'High',
                'assigned_to' => $staff->id,
                'due_at' => now()->addDays(2),
                'reporter_name' => 'Maria Santos',
                'reporter_contact' => '09171234567',
                'reporter_email' => 'maria@example.com',
                'lat' => 6.9214,
                'lng' => 122.0790,
            ]
        );

        $assignedReport->internalNotes()->firstOrCreate(
            ['body' => 'Spoke with engineering maintenance team. Work order scheduled for Friday morning.'],
            ['user_id' => $staff->id]
        );

        // 2. Report with "Needs Info" request
        $needsInfoReport = Report::firstOrCreate(
            ['tracking_code' => 'BRG-STF002'],
            [
                'description' => 'Streetlight flickering and turning off intermittently near the chapel.',
                'staff_description' => 'Intermittent streetlight failure.',
                'purok_id' => $purok?->id,
                'category_id' => $category?->id,
                'status' => ReportStatus::NEEDS_INFO,
                'urgency' => 'Medium',
                'assigned_to' => $staff->id,
                'due_at' => now()->addDays(1),
                'reporter_name' => 'Juan Dela Cruz',
                'reporter_contact' => '09189876543',
                'lat' => 6.9220,
                'lng' => 122.0795,
            ]
        );

        $needsInfoReport->infoRequests()->firstOrCreate(
            ['question' => 'Could you provide the exact pole number or the nearest house landmark?'],
            [
                'requested_by' => $staff->id,
                'response' => 'It is the concrete post right in front of house #42, beside the mango tree.',
                'responded_at' => now()->subHours(2),
            ]
        );

        // 3. Overdue active report
        Report::firstOrCreate(
            ['tracking_code' => 'BRG-OVD001'],
            [
                'description' => 'Uncollected garbage accumulating near public market stall.',
                'staff_description' => 'Solid waste accumulation.',
                'purok_id' => $purok?->id,
                'category_id' => $category?->id,
                'status' => ReportStatus::UNDER_REVIEW,
                'urgency' => 'High',
                'assigned_to' => $staff->id,
                'due_at' => now()->subDay(), // Overdue
                'lat' => 6.9230,
                'lng' => 122.0800,
            ]
        );

        // 4. Case with Resolution Proposed (Awaiting Confirmation)
        $resolutionReport = Report::firstOrCreate(
            ['tracking_code' => 'BRG-RES001'],
            [
                'description' => 'Fallen tree branch obstructing foot traffic on sidewalk.',
                'staff_description' => 'Tree branch obstacle cleared.',
                'purok_id' => $purok?->id,
                'category_id' => $category?->id,
                'status' => ReportStatus::AWAITING_CONFIRMATION,
                'urgency' => 'Medium',
                'assigned_to' => $staff->id,
                'due_at' => now()->addDays(1),
                'lat' => 6.9240,
                'lng' => 122.0810,
            ]
        );

        $resolutionReport->resolutionProofs()->firstOrCreate(
            ['note' => 'Barangay cleanup crew cleared and disposed of the fallen tree branches. Walkway is now clear.'],
            ['user_id' => $staff->id]
        );

        // 5. Reopened case
        $reopenedReport = Report::firstOrCreate(
            ['tracking_code' => 'BRG-REO001'],
            [
                'description' => 'Pothole temporarily covered with gravel washed away after rain.',
                'staff_description' => 'Pothole reappeared following downpour.',
                'purok_id' => $purok?->id,
                'category_id' => $category?->id,
                'status' => ReportStatus::REOPENED,
                'reopened_count' => 1,
                'urgency' => 'High',
                'assigned_to' => $staff->id,
                'due_at' => now()->addHours(12),
                'lat' => 6.9250,
                'lng' => 122.0820,
            ]
        );

        // 6. Escalated case
        Report::firstOrCreate(
            ['tracking_code' => 'BRG-ESC001'],
            [
                'description' => 'Exposed low-hanging electrical wire near basketball court.',
                'staff_description' => 'Hazardous electrical wire.',
                'purok_id' => $purok?->id,
                'category_id' => $category?->id,
                'status' => ReportStatus::IN_PROGRESS,
                'urgency' => 'Critical',
                'assigned_to' => $admin?->id ?? $staff->id,
                'due_at' => now()->subHours(6),
                'escalated_at' => now()->subHours(1),
                'lat' => 6.9260,
                'lng' => 122.0830,
            ]
        );
    }
}
