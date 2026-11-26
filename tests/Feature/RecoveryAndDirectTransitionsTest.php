<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DuplicateSuggestion;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use App\Support\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecoveryAndDirectTransitionsTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private User $resident;

    private Purok $purok;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->staff()->create();
        $this->staff->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
        ]);
        $this->resident = User::factory()->create(['role' => 'resident']);
        $this->purok = Purok::factory()->create(['name' => 'Purok 1']);
        $office = Office::create([
            'name' => 'Barangay Works',
            'type' => 'barangay',
            'is_internal' => true,
        ]);
        $this->category = Category::create([
            'name' => 'Drainage',
            'slug' => 'drainage',
            'default_office_id' => $office->id,
            'is_active' => true,
        ]);
    }

    public function test_staff_can_unmerge_report_with_required_note_and_audit_log(): void
    {
        $parent = Report::factory()->create([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::UNDER_REVIEW,
        ]);

        $duplicate = Report::factory()->create([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::MERGED,
            'merged_into_id' => $parent->id,
        ]);

        DuplicateSuggestion::create([
            'report_id' => $duplicate->id,
            'candidate_id' => $parent->id,
            'score' => 0.90,
            'decision' => 'merged',
            'decided_by' => $this->staff->id,
        ]);

        // 1. Missing note fails validation
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.unmerge', $duplicate), [
                'note' => '',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('note');

        // Note too short fails
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.unmerge', $duplicate), [
                'note' => 'No',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('note');

        // 2. Successful unmerge
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.unmerge', $duplicate), [
                'note' => 'Upon field inspection, this is a distinct location from the parent report.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $duplicate->refresh();
        $this->assertSame(ReportStatus::UNDER_REVIEW, $duplicate->status);
        $this->assertNull($duplicate->merged_into_id);

        $this->assertDatabaseHas('duplicate_suggestions', [
            'report_id' => $duplicate->id,
            'candidate_id' => $parent->id,
            'decision' => 'unmerged',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->staff->id,
            'action' => 'unmerge_report',
            'model' => Report::class,
            'model_id' => $duplicate->id,
        ]);

        $this->assertDatabaseHas('status_logs', [
            'report_id' => $duplicate->id,
            'from_status' => ReportStatus::MERGED,
            'to_status' => ReportStatus::UNDER_REVIEW,
        ]);
    }

    public function test_resident_and_guest_cannot_unmerge_report(): void
    {
        $duplicate = Report::factory()->create([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::MERGED,
        ]);

        // Unauthenticated guest is redirected to login
        $this->patch(route('admin.reports.unmerge', $duplicate), [
            'note' => 'Attempting unmerge without authentication.',
        ])->assertRedirect(route('login'));

        // Authenticated resident is forbidden (403)
        $this->actingAs($this->resident)
            ->patch(route('admin.reports.unmerge', $duplicate), [
                'note' => 'Attempting unmerge as resident.',
            ])
            ->assertForbidden();

        $this->assertSame(ReportStatus::MERGED, $duplicate->fresh()->status);
    }

    public function test_staff_can_transition_emergency_report_to_in_progress_with_audit_log(): void
    {
        $emergencyReport = Report::factory()->create([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::EMERGENCY_FLAGGED,
            'is_emergency' => true,
        ]);

        // Missing note fails validation
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.status', $emergencyReport), [
                'status' => ReportStatus::IN_PROGRESS,
                'note' => '',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('note');

        // Valid submission moves emergency report to in_progress
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.status', $emergencyReport), [
                'status' => ReportStatus::IN_PROGRESS,
                'note' => 'Barangay Tanods were dispatched and attended to the hazard on-site.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $emergencyReport->refresh();
        $this->assertSame(ReportStatus::IN_PROGRESS, $emergencyReport->status);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->staff->id,
            'action' => 'emergency_handled_internally',
            'model' => Report::class,
            'model_id' => $emergencyReport->id,
        ]);

        $this->assertDatabaseHas('status_logs', [
            'report_id' => $emergencyReport->id,
            'from_status' => ReportStatus::EMERGENCY_FLAGGED,
            'to_status' => ReportStatus::IN_PROGRESS,
            'note' => 'Barangay Tanods were dispatched and attended to the hazard on-site.',
        ]);
    }

    public function test_resident_and_guest_cannot_update_emergency_status(): void
    {
        $emergencyReport = Report::factory()->create([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::EMERGENCY_FLAGGED,
            'is_emergency' => true,
        ]);

        // Guest redirected
        $this->patch(route('admin.reports.status', $emergencyReport), [
            'status' => ReportStatus::IN_PROGRESS,
            'note' => 'Unauthorized update.',
        ])->assertRedirect(route('login'));

        // Resident forbidden
        $this->actingAs($this->resident)
            ->patch(route('admin.reports.status', $emergencyReport), [
                'status' => ReportStatus::IN_PROGRESS,
                'note' => 'Unauthorized update.',
            ])->assertForbidden();

        $this->assertSame(ReportStatus::EMERGENCY_FLAGGED, $emergencyReport->fresh()->status);
    }

    public function test_resuming_no_action_report_creates_audit_log_and_clears_reason(): void
    {
        $noActionReport = Report::factory()->create([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::NO_ACTION,
            'no_action_reason' => ReportStatus::REASON_CANNOT_RESOLVE,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.reopen', $noActionReport), [
                'reason' => 'Barangay acquired new equipment to resolve the technical obstacle.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $noActionReport->refresh();
        $this->assertSame(ReportStatus::IN_PROGRESS, $noActionReport->status);
        $this->assertNull($noActionReport->no_action_reason);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->staff->id,
            'action' => 'resume_no_action_report',
            'model' => Report::class,
            'model_id' => $noActionReport->id,
        ]);
    }

    public function test_resident_and_guest_cannot_reopen_no_action_report(): void
    {
        $noActionReport = Report::factory()->create([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::NO_ACTION,
            'no_action_reason' => ReportStatus::REASON_CANNOT_RESOLVE,
        ]);

        // Guest redirected
        $this->post(route('admin.reports.reopen', $noActionReport), [
            'reason' => 'Unauthorized reopen.',
        ])->assertRedirect(route('login'));

        // Resident forbidden
        $this->actingAs($this->resident)
            ->post(route('admin.reports.reopen', $noActionReport), [
                'reason' => 'Unauthorized reopen as resident.',
            ])->assertForbidden();

        $this->assertSame(ReportStatus::NO_ACTION, $noActionReport->fresh()->status);
    }
}
