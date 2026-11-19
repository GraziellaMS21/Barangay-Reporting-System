<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Office;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use App\Support\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoActionStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Purok $purok;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->staff()->create();
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

    public function test_transition_to_no_action_requires_valid_reason_code_and_note(): void
    {
        $report = Report::factory()->create([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::SUBMITTED,
        ]);

        // Missing both reason and note fails
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.status', $report), [
                'status' => ReportStatus::NO_ACTION,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['note', 'no_action_reason']);

        // Invalid reason code fails
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.status', $report), [
                'status' => ReportStatus::NO_ACTION,
                'no_action_reason' => 'invalid_code_xyz',
                'note' => 'Some explanation note.',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('no_action_reason');

        // Valid reason code and note succeeds
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.status', $report), [
                'status' => ReportStatus::NO_ACTION,
                'no_action_reason' => ReportStatus::REASON_NOT_BARANGAY,
                'note' => 'This is a private property boundary dispute outside barangay authority.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame(ReportStatus::NO_ACTION, $report->status);
        $this->assertSame(ReportStatus::REASON_NOT_BARANGAY, $report->no_action_reason);

        $this->assertDatabaseHas('status_logs', [
            'report_id' => $report->id,
            'from_status' => ReportStatus::SUBMITTED,
            'to_status' => ReportStatus::NO_ACTION,
            'no_action_reason' => ReportStatus::REASON_NOT_BARANGAY,
            'note' => 'This is a private property boundary dispute outside barangay authority.',
        ]);
    }

    public function test_staff_can_reopen_no_action_report_clearing_reason(): void
    {
        $report = Report::factory()->create([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::NO_ACTION,
            'no_action_reason' => ReportStatus::REASON_CANNOT_RESOLVE,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.reopen', $report), [
                'reason' => 'New equipment is now available to resolve this issue.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame(ReportStatus::IN_PROGRESS, $report->status);
        $this->assertNull($report->no_action_reason);

        $this->assertDatabaseHas('status_logs', [
            'report_id' => $report->id,
            'from_status' => ReportStatus::NO_ACTION,
            'to_status' => ReportStatus::IN_PROGRESS,
            'note' => 'Report reopened by staff: New equipment is now available to resolve this issue.',
        ]);
    }

    public function test_resident_tracking_page_shows_no_action_notice_and_reason(): void
    {
        $report = Report::factory()->create([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::NO_ACTION,
            'no_action_reason' => ReportStatus::REASON_NOT_BARANGAY,
        ]);

        $report->statusLogs()->create([
            'user_id' => $this->staff->id,
            'from_status' => ReportStatus::SUBMITTED,
            'to_status' => ReportStatus::NO_ACTION,
            'no_action_reason' => ReportStatus::REASON_NOT_BARANGAY,
            'note' => 'Issue is beyond barangay scope and must be addressed with private landowners.',
        ]);

        $this->get(route('track.show', $report->tracking_code))
            ->assertOk()
            ->assertSee('Case Closed: No Further Action')
            ->assertSee('Not a barangay matter')
            ->assertSee('Issue is beyond barangay scope and must be addressed with private landowners.');
    }
}
