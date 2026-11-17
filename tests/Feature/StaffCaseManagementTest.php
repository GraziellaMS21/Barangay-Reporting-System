<?php

namespace Tests\Feature;

use App\Events\InfoRequested;
use App\Events\ReportStatusChanged;
use App\Events\ResolutionProposed;
use App\Models\Category;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportStatusService;
use App\Support\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffCaseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private User $admin;

    private Category $category;

    private Purok $purok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->staff()->create(['name' => 'Officer Staff']);
        $this->admin = User::factory()->admin()->create(['name' => 'Chief Admin']);
        $this->purok = Purok::factory()->create(['name' => 'Purok 1']);
        $this->category = Category::create([
            'name' => 'Infrastructure',
            'slug' => 'infrastructure',
            'is_active' => true,
        ]);
    }

    public function test_staff_can_view_queue_and_filter_by_assignee(): void
    {
        $assignedReport = Report::factory()->create([
            'tracking_code' => 'BRG-ASN001',
            'assigned_to' => $this->staff->id,
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::IN_PROGRESS,
            'due_at' => now()->addDay(),
        ]);

        $unassignedReport = Report::factory()->create([
            'tracking_code' => 'BRG-UNA001',
            'assigned_to' => null,
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => ReportStatus::SUBMITTED,
        ]);

        // Unfiltered queue
        $this->actingAs($this->staff)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee($assignedReport->tracking_code)
            ->assertSee($unassignedReport->tracking_code)
            ->assertSee('Officer Staff');

        // Filter by assigned staff
        $this->get(route('admin.reports.index', ['assigned_to' => $this->staff->id]))
            ->assertOk()
            ->assertSee($assignedReport->tracking_code)
            ->assertDontSee($unassignedReport->tracking_code);

        // Filter unassigned
        $this->get(route('admin.reports.index', ['assigned_to' => 'unassigned']))
            ->assertOk()
            ->assertSee($unassignedReport->tracking_code)
            ->assertDontSee($assignedReport->tracking_code);
    }

    public function test_staff_can_view_my_cases_page(): void
    {
        $myReport = Report::factory()->create([
            'tracking_code' => 'BRG-MINE01',
            'assigned_to' => $this->staff->id,
            'status' => ReportStatus::IN_PROGRESS,
        ]);

        $otherReport = Report::factory()->create([
            'tracking_code' => 'BRG-OTHER1',
            'assigned_to' => $this->admin->id,
            'status' => ReportStatus::IN_PROGRESS,
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.reports.my-cases'))
            ->assertOk()
            ->assertSee('My assigned cases')
            ->assertSee($myReport->tracking_code)
            ->assertDontSee($otherReport->tracking_code);
    }

    public function test_queue_displays_overdue_and_reopened_badges(): void
    {
        $overdueReport = Report::factory()->create([
            'tracking_code' => 'BRG-OVR001',
            'status' => ReportStatus::UNDER_REVIEW,
            'due_at' => now()->subDay(),
            'reopened_count' => 2,
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee($overdueReport->tracking_code)
            ->assertSee('title="Overdue"', false)
            ->assertSee('title="Reopened"', false);
    }

    public function test_staff_can_assign_report(): void
    {
        $report = Report::factory()->create([
            'assigned_to' => null,
        ]);

        $this->actingAs($this->staff)
            ->patch(route('admin.reports.assign', $report), [
                'assigned_to' => $this->staff->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'assigned_to' => $this->staff->id,
        ]);
    }

    public function test_staff_can_request_more_info_from_resident(): void
    {
        Event::fake([InfoRequested::class, ReportStatusChanged::class]);

        $report = Report::factory()->create([
            'status' => ReportStatus::UNDER_REVIEW,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.info-requests.store', $report), [
                'question' => 'Please provide the exact street address or corner.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('info_requests', [
            'report_id' => $report->id,
            'requested_by' => $this->staff->id,
            'question' => 'Please provide the exact street address or corner.',
        ]);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => ReportStatus::NEEDS_INFO,
        ]);

        Event::assertDispatched(InfoRequested::class, function ($event) use ($report) {
            return $event->report->id === $report->id &&
                $event->infoRequest->question === 'Please provide the exact street address or corner.';
        });

        Event::assertDispatched(ReportStatusChanged::class, function ($event) use ($report) {
            return $event->report->id === $report->id &&
                $event->to === ReportStatus::NEEDS_INFO &&
                $event->userId === $this->staff->id;
        });
    }

    public function test_staff_can_add_internal_notes(): void
    {
        $report = Report::factory()->create();

        $this->actingAs($this->staff)
            ->post(route('admin.reports.internal-notes.store', $report), [
                'body' => 'Spoke with DPWH district office regarding water drainage pipes.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('internal_notes', [
            'report_id' => $report->id,
            'user_id' => $this->staff->id,
            'body' => 'Spoke with DPWH district office regarding water drainage pipes.',
        ]);
    }

    public function test_staff_can_propose_resolution_with_proof(): void
    {
        Event::fake([ResolutionProposed::class, ReportStatusChanged::class]);
        Storage::fake('local');

        $report = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
        ]);

        $file = UploadedFile::fake()->image('repair.jpg');

        $this->actingAs($this->staff)
            ->post(route('admin.reports.resolve', $report), [
                'note' => 'Fixed streetlight fixture and replaced bulb.',
                'photo' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => ReportStatus::AWAITING_CONFIRMATION,
        ]);

        $this->assertDatabaseHas('resolution_proofs', [
            'report_id' => $report->id,
            'user_id' => $this->staff->id,
            'note' => 'Fixed streetlight fixture and replaced bulb.',
        ]);

        Event::assertDispatched(ResolutionProposed::class);
        Event::assertDispatched(ReportStatusChanged::class);
    }

    public function test_staff_can_close_case_manually(): void
    {
        $report = Report::factory()->create([
            'status' => ReportStatus::AWAITING_CONFIRMATION,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.close', $report), [
                'note' => 'Closed after auto-close period expired.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => ReportStatus::RESOLVED,
        ]);
    }

    public function test_staff_can_escalate_overdue_case(): void
    {
        $report = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
            'due_at' => now()->subDay(),
            'escalated_at' => null,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.escalate', $report), [
                'reason' => 'Emergency response delayed by weather.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertNotNull($report->escalated_at);

        $this->assertDatabaseHas('status_logs', [
            'report_id' => $report->id,
            'user_id' => $this->staff->id,
        ]);
    }

    public function test_review_page_renders_phase_11_engine_panels(): void
    {
        $report = Report::factory()->create([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee('Review AI suggestions')
            ->assertSee('Route and assign')
            ->assertSee('data-routing-map', false)
            ->assertSee('Mark duplicate of')
            ->assertSee('Refer to office');
    }

    public function test_report_status_service_is_the_single_place_status_logs_are_written(): void
    {
        Event::fake([ReportStatusChanged::class]);

        $report = Report::factory()->create(['status' => ReportStatus::SUBMITTED]);
        $service = app(ReportStatusService::class);

        $service->transition($report, ReportStatus::UNDER_REVIEW, 'Review initiated', $this->staff);

        $this->assertSame(ReportStatus::UNDER_REVIEW, $report->fresh()->status);
        $this->assertDatabaseHas('status_logs', [
            'report_id' => $report->id,
            'user_id' => $this->staff->id,
            'from_status' => ReportStatus::SUBMITTED,
            'to_status' => ReportStatus::UNDER_REVIEW,
            'note' => 'Review initiated',
        ]);

        Event::assertDispatched(ReportStatusChanged::class, function ($event) use ($report) {
            return $event->report->id === $report->id &&
                $event->from === ReportStatus::SUBMITTED &&
                $event->to === ReportStatus::UNDER_REVIEW &&
                $event->note === 'Review initiated' &&
                $event->userId === $this->staff->id;
        });
    }
}
