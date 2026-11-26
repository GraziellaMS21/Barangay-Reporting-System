<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\DuplicateSuggestion;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Report;
use App\Models\ReportAiResult;
use App\Models\RoutingRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateDecisionTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private User $otherStaff;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->staff()->create();
        $this->staff->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
            Permission::firstOrCreate(['slug' => 'dashboard.view'], ['label' => 'View Dashboard Analytics', 'group' => 'dashboard'])->id,
        ]);
        $this->otherStaff = User::factory()->staff()->create();
        $this->category = Category::query()->create([
            'name' => 'Drainage & Flooding',
            'slug' => 'drainage-and-flooding',
            'is_active' => true,
        ]);
    }

    public function test_staff_can_dismiss_pending_duplicate_suggestion_and_records_audit_log(): void
    {
        $report = Report::factory()->create([
            'category_id' => $this->category->id,
            'status' => 'submitted',
        ]);
        $candidate = Report::factory()->create([
            'category_id' => $this->category->id,
            'status' => 'under_review',
        ]);
        $suggestion = DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'score' => 0.82,
            'decision' => 'pending',
        ]);

        $response = $this->actingAs($this->staff)
            ->patch(route('admin.reports.duplicate.dismiss', [$report, $suggestion]), [
                'note' => 'Different street corner upon photo inspection.',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Duplicate suggestion dismissed.');

        $this->assertDatabaseHas('duplicate_suggestions', [
            'id' => $suggestion->id,
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'decision' => 'dismissed',
            'decided_by' => $this->staff->id,
        ]);

        $report->refresh();
        $candidate->refresh();
        $this->assertSame('submitted', $report->status);
        $this->assertNull($report->merged_into_id);
        $this->assertSame('under_review', $candidate->status);

        $auditLog = AuditLog::query()
            ->where('action', 'dismiss_duplicate_suggestion')
            ->where('model', Report::class)
            ->where('model_id', $report->id)
            ->firstOrFail();

        $this->assertSame($this->staff->id, $auditLog->user_id);
        $this->assertSame('pending', $auditLog->old_values['decision']);
        $this->assertSame('dismissed', $auditLog->new_values['decision']);
        $this->assertSame($suggestion->id, $auditLog->new_values['suggestion_id']);
        $this->assertSame($candidate->id, $auditLog->new_values['candidate_id']);
        $this->assertSame($this->staff->id, $auditLog->new_values['decided_by']);
        $this->assertSame('Different street corner upon photo inspection.', $auditLog->new_values['note']);
    }

    public function test_dismissing_suggestion_belonging_to_another_report_returns_404(): void
    {
        $reportA = Report::factory()->create(['category_id' => $this->category->id]);
        $reportB = Report::factory()->create(['category_id' => $this->category->id]);
        $candidate = Report::factory()->create(['category_id' => $this->category->id]);

        $suggestionForB = DuplicateSuggestion::create([
            'report_id' => $reportB->id,
            'candidate_id' => $candidate->id,
            'score' => 0.75,
            'decision' => 'pending',
        ]);

        $this->actingAs($this->staff)
            ->patch(route('admin.reports.duplicate.dismiss', [$reportA, $suggestionForB]))
            ->assertNotFound();

        $this->assertDatabaseHas('duplicate_suggestions', [
            'id' => $suggestionForB->id,
            'decision' => 'pending',
            'decided_by' => null,
        ]);
    }

    public function test_non_pending_suggestions_cannot_be_dismissed_or_overwritten(): void
    {
        $report = Report::factory()->create(['category_id' => $this->category->id]);

        $cases = ['dismissed', 'merged', 'unmerged'];
        foreach ($cases as $decision) {
            $candidate = Report::factory()->create(['category_id' => $this->category->id]);
            $suggestion = DuplicateSuggestion::create([
                'report_id' => $report->id,
                'candidate_id' => $candidate->id,
                'score' => 0.88,
                'decision' => $decision,
                'decided_by' => $this->otherStaff->id,
            ]);

            $this->actingAs($this->staff)
                ->patch(route('admin.reports.duplicate.dismiss', [$report, $suggestion]), [
                    'note' => 'Attempting to overwrite resolved decision.',
                ])
                ->assertRedirect()
                ->assertSessionHasErrors(['duplicate']);

            $this->assertDatabaseHas('duplicate_suggestions', [
                'id' => $suggestion->id,
                'decision' => $decision,
                'decided_by' => $this->otherStaff->id,
            ]);
        }

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_guest_and_non_staff_users_cannot_dismiss_duplicate_suggestions(): void
    {
        $report = Report::factory()->create(['category_id' => $this->category->id]);
        $candidate = Report::factory()->create(['category_id' => $this->category->id]);
        $suggestion = DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'score' => 0.70,
            'decision' => 'pending',
        ]);

        $this->patch(route('admin.reports.duplicate.dismiss', [$report, $suggestion]))
            ->assertRedirect(route('login'));

        $resident = User::factory()->create(['role' => 'resident']);
        $this->actingAs($resident)
            ->patch(route('admin.reports.duplicate.dismiss', [$report, $suggestion]))
            ->assertForbidden();

        $this->assertDatabaseHas('duplicate_suggestions', [
            'id' => $suggestion->id,
            'decision' => 'pending',
            'decided_by' => null,
        ]);
    }

    public function test_dismissing_suggestion_decreases_dashboard_pending_count_and_unblocks_bulk_confirm(): void
    {
        $office = Office::create([
            'name' => 'Barangay Maintenance',
            'type' => 'internal',
            'is_internal' => true,
            'is_active' => true,
        ]);
        RoutingRule::create([
            'category_id' => $this->category->id,
            'office_id' => $office->id,
            'within_authority' => true,
            'priority' => 1,
        ]);

        $report = Report::factory()->create([
            'category_id' => $this->category->id,
            'status' => 'submitted',
            'urgency' => 'Medium',
            'ai_status' => 'done',
            'is_emergency' => false,
        ]);
        ReportAiResult::create([
            'report_id' => $report->id,
            'suggested_category_id' => $this->category->id,
            'urgency' => 'Medium',
            'confidence' => 0.95,
        ]);

        $candidate = Report::factory()->create([
            'category_id' => $this->category->id,
            'status' => 'under_review',
        ]);
        $suggestion = DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'score' => 0.78,
            'decision' => 'pending',
        ]);

        // Dashboard shows Pending duplicates = 1
        $this->actingAs($this->staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pending duplicates');

        // Bulk confirm skips the report while suggestion is pending
        $this->actingAs($this->staff)
            ->post(route('admin.reports.bulk-confirm'), ['report_ids' => [$report->id]])
            ->assertRedirect()
            ->assertSessionHas('success', 'Confirmed and routed 0 report(s).');

        $this->assertSame('submitted', $report->fresh()->status);

        // Staff dismisses the suggestion
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.duplicate.dismiss', [$report, $suggestion]))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Dashboard no longer shows Pending duplicates
        $this->actingAs($this->staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Pending duplicates');

        // Bulk confirm now routes the report
        $this->actingAs($this->staff)
            ->post(route('admin.reports.bulk-confirm'), ['report_ids' => [$report->id]])
            ->assertRedirect()
            ->assertSessionHas('success', 'Confirmed and routed 1 report(s).');

        $this->assertSame('routed', $report->fresh()->status);
    }

    public function test_existing_merge_unmerge_and_emergency_protection_remain_intact(): void
    {
        $report = Report::factory()->create([
            'category_id' => $this->category->id,
            'status' => 'submitted',
            'is_emergency' => false,
        ]);
        $candidate = Report::factory()->create([
            'category_id' => $this->category->id,
            'status' => 'under_review',
        ]);
        $suggestion = DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'score' => 0.91,
            'decision' => 'pending',
        ]);

        // Merge
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.duplicate', $report), [
                'candidate_id' => $candidate->id,
                'note' => 'Confirmed identical drainage issue.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Report marked as a duplicate.');

        $report->refresh();
        $this->assertSame('merged', $report->status);
        $this->assertSame($candidate->id, $report->merged_into_id);
        $this->assertDatabaseHas('duplicate_suggestions', [
            'id' => $suggestion->id,
            'decision' => 'merged',
            'decided_by' => $this->staff->id,
        ]);

        // Unmerge
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.unmerge', $report), [
                'note' => 'Different location confirmed after inspection.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Report unmerged and returned to review.');

        $report->refresh();
        $this->assertSame('under_review', $report->status);
        $this->assertNull($report->merged_into_id);
        $this->assertDatabaseHas('duplicate_suggestions', [
            'id' => $suggestion->id,
            'decision' => 'unmerged',
        ]);

        // Emergency cannot be merged
        $emergencyReport = Report::factory()->create([
            'category_id' => $this->category->id,
            'status' => 'emergency_flagged',
            'is_emergency' => true,
        ]);

        $this->actingAs($this->staff)
            ->patch(route('admin.reports.duplicate', $emergencyReport), [
                'candidate_id' => $candidate->id,
                'note' => 'Should be blocked.',
            ])
            ->assertStatus(409);

        $this->assertSame('emergency_flagged', $emergencyReport->fresh()->status);
    }
}
