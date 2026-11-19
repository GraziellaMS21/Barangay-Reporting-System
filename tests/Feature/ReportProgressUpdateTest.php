<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Office;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportProgressUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Category $category;

    private Purok $purok;

    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->staff()->create(['name' => 'Staff Member']);
        $this->purok = Purok::factory()->create(['name' => 'Purok 1']);
        $this->category = Category::create([
            'name' => 'Garbage & Sanitation',
            'slug' => 'garbage-sanitation',
            'is_active' => true,
        ]);
        $this->office = Office::create([
            'name' => 'Barangay Sanitation Office',
            'type' => 'barangay committee',
            'is_internal' => true,
            'is_external' => false,
        ]);
    }

    private function createRoutedReport(array $overrides = []): Report
    {
        return Report::factory()->create(array_merge([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'office_id' => $this->office->id,
            'status' => 'routed',
        ], $overrides));
    }

    public function test_posting_progress_updates_changes_report_status_and_creates_timeline_entry(): void
    {
        Storage::fake('public');

        $report = $this->createRoutedReport();
        $expectedDate = now()->addDays(3)->toDateString();

        // 1. Inspection scheduled -> in_progress
        $this->actingAs($this->staff)
            ->post(route('admin.reports.updates.store', $report), [
                'update_type' => 'inspection_scheduled',
                'message' => 'Inspection team will visit tomorrow morning.',
                'expected_completion_date' => $expectedDate,
                'visible_to_resident' => '1',
                'photos' => [
                    UploadedFile::fake()->image('site-check.jpg', 600, 400),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('in_progress', $report->status);

        $log = $report->statusLogs()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('inspection_scheduled', $log->update_type);
        $this->assertSame('Inspection scheduled', $log->update_title);
        $this->assertSame('Inspection team will visit tomorrow morning.', $log->note);
        $this->assertSame($expectedDate, $log->expected_completion_date?->toDateString());
        $this->assertTrue($log->visible_to_resident);
        $this->assertSame($this->office->id, $log->office_id);
        $this->assertSame($this->staff->id, $log->user_id);
        $this->assertCount(1, $log->attachments);
        Storage::disk('public')->assertExists($log->attachments[0]);

        // 2. Waiting on materials/equipment -> in_progress (progress note, self-transition)
        $this->actingAs($this->staff)
            ->post(route('admin.reports.updates.store', $report), [
                'update_type' => 'waiting_materials',
                'message' => 'Waiting for replacement drainage grates.',
                'visible_to_resident' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('in_progress', $report->status);

        // 3. Cannot be resolved -> closed
        $this->actingAs($this->staff)
            ->post(route('admin.reports.updates.store', $report), [
                'update_type' => 'cannot_resolve',
                'message' => 'Issue is on private property outside barangay authority.',
                'visible_to_resident' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('closed', $report->status);
    }

    public function test_required_message_and_date_validation_on_progress_update(): void
    {
        $report = $this->createRoutedReport();

        // Update types that require a message: needs_resident_input, referred_agency, cannot_resolve
        foreach (['needs_resident_input', 'referred_agency', 'cannot_resolve'] as $requiredType) {
            $this->actingAs($this->staff)
                ->post(route('admin.reports.updates.store', $report), [
                    'update_type' => $requiredType,
                    'message' => '',
                ])
                ->assertSessionHasErrors('message');
        }

        // Past expected completion date is rejected
        $this->actingAs($this->staff)
            ->post(route('admin.reports.updates.store', $report), [
                'update_type' => 'work_started',
                'message' => 'Crew dispatched.',
                'expected_completion_date' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors('expected_completion_date');

        // Optional message update type succeeds even when message is empty (uses template fallback)
        $this->actingAs($this->staff)
            ->post(route('admin.reports.updates.store', $report), [
                'update_type' => 'work_started',
                'message' => '',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $report->refresh();
        $this->assertSame('in_progress', $report->status);
    }

    public function test_internal_notes_are_hidden_from_resident_tracking_page_while_public_updates_are_shown(): void
    {
        $report = $this->createRoutedReport();
        $expectedDate = now()->addDays(2)->toDateString();

        // Public update
        $this->actingAs($this->staff)
            ->post(route('admin.reports.updates.store', $report), [
                'update_type' => 'work_started',
                'message' => 'Sanitation crew has started clearing the area.',
                'expected_completion_date' => $expectedDate,
                'visible_to_resident' => '1',
            ])
            ->assertRedirect();

        // Internal update (visible_to_resident = 0)
        $this->actingAs($this->staff)
            ->post(route('admin.reports.updates.store', $report), [
                'update_type' => 'waiting_materials',
                'message' => 'Internal note: truck #2 needs tire repair before second trip.',
                'visible_to_resident' => '0',
            ])
            ->assertRedirect();

        $response = $this->get(route('track.show', $report->tracking_code));

        $response->assertOk()
            ->assertSee('Work started / Crew dispatched')
            ->assertSee('Sanitation crew has started clearing the area.')
            ->assertSee('Barangay Sanitation Office')
            ->assertSee('Expected completion: '.now()->addDays(2)->format('M d, Y'))
            ->assertDontSee('Internal note: truck #2 needs tire repair before second trip.')
            ->assertDontSee('Staff Member');
    }

    public function test_staff_can_reopen_resolved_or_closed_report_with_reason(): void
    {
        $report = $this->createRoutedReport(['status' => 'closed']);

        // Missing reason fails validation
        $this->actingAs($this->staff)
            ->post(route('admin.reports.reopen', $report), [
                'reason' => '',
            ])
            ->assertSessionHasErrors('reason');

        // Valid reason reopens to in_progress
        $this->actingAs($this->staff)
            ->post(route('admin.reports.reopen', $report), [
                'reason' => 'New information received confirming the issue is on public road.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('in_progress', $report->status);
        $this->assertDatabaseHas('status_logs', [
            'report_id' => $report->id,
            'from_status' => 'closed',
            'to_status' => 'in_progress',
        ]);
    }

    public function test_unauthorized_user_cannot_post_updates_or_reopen(): void
    {
        $report = $this->createRoutedReport();

        // Unauthenticated guest is redirected to login
        $this->post(route('admin.reports.updates.store', $report), [
            'update_type' => 'work_started',
            'message' => 'Unauthorized attempt.',
        ])->assertRedirect(route('login'));

        $report->refresh();
        $this->assertSame('routed', $report->status);
    }
}
