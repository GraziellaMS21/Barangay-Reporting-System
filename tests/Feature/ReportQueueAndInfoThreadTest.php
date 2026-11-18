<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InfoRequest;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use App\Support\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportQueueAndInfoThreadTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Category $category;

    private Purok $purok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->staff()->create(['name' => 'Officer Staff']);
        $this->purok = Purok::factory()->create(['name' => 'Purok 1']);
        $this->category = Category::create([
            'name' => 'Garbage',
            'slug' => 'garbage',
            'is_active' => true,
        ]);
    }

    private function createReport(string $code, array $attributes = []): Report
    {
        return Report::factory()->create(array_merge([
            'tracking_code' => $code,
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
        ], $attributes));
    }

    public function test_queue_defaults_to_newest_first(): void
    {
        $oldest = $this->createReport('BRG-OLD001', ['created_at' => now()->subDays(2)]);
        $newest = $this->createReport('BRG-NEW001', ['created_at' => now()->subHour()]);

        $content = $this->actingAs($this->staff)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->getContent();

        $this->assertTrue(
            strpos($content, $newest->tracking_code) < strpos($content, $oldest->tracking_code),
            'Expected the newest report to be listed first by default.'
        );
    }

    public function test_queue_supports_whitelisted_sorting(): void
    {
        $older = $this->createReport('BRG-SRT002', ['created_at' => now()->subDay()]);
        $newer = $this->createReport('BRG-SRT001', ['created_at' => now()]);

        // Ascending by submitted puts the oldest first.
        $content = $this->actingAs($this->staff)
            ->get(route('admin.reports.index', ['sort' => 'submitted', 'direction' => 'asc']))
            ->assertOk()
            ->getContent();
        $this->assertTrue(
            strpos($content, $older->tracking_code) < strpos($content, $newer->tracking_code),
            'Expected ascending submitted sort to list the oldest report first.'
        );

        // Descending puts the newest first.
        $content = $this->actingAs($this->staff)
            ->get(route('admin.reports.index', ['sort' => 'submitted', 'direction' => 'desc']))
            ->assertOk()
            ->getContent();
        $this->assertTrue(
            strpos($content, $newer->tracking_code) < strpos($content, $older->tracking_code),
            'Expected descending submitted sort to list the newest report first.'
        );

        // An unknown sort parameter is ignored and falls back to the default order.
        $this->actingAs($this->staff)
            ->get(route('admin.reports.index', ['sort' => 'drop-table', 'direction' => 'sideways']))
            ->assertOk()
            ->assertSee($newer->tracking_code);
    }

    public function test_queue_filters_by_category(): void
    {
        $garbage = $this->createReport('BRG-GARB01', ['category_id' => $this->category->id]);
        $otherCategory = Category::create([
            'name' => 'Streetlight',
            'slug' => 'streetlight',
            'is_active' => true,
        ]);
        $streetlight = $this->createReport('BRG-STRT01', ['category_id' => $otherCategory->id]);

        $this->actingAs($this->staff)
            ->get(route('admin.reports.index', ['category_id' => $this->category->id]))
            ->assertOk()
            ->assertSee($garbage->tracking_code)
            ->assertDontSee($streetlight->tracking_code);
    }

    public function test_json_message_send_creates_info_request_and_status_log(): void
    {
        $report = $this->createReport('BRG-CHAT01');

        $response = $this->actingAs($this->staff)
            ->postJson(route('admin.reports.info-requests.store', $report), [
                'question' => 'What time did you first notice this issue?',
            ])
            ->assertCreated()
            ->assertJsonStructure([
                'report_status',
                'summary' => ['count', 'awaiting_reply', 'unread'],
                'messages' => [['id', 'sender', 'sender_name', 'text', 'at']],
            ]);

        $response->assertJsonPath('summary.count', 1)
            ->assertJsonPath('summary.awaiting_reply', true)
            ->assertJsonPath('messages.0.sender', 'staff')
            ->assertJsonPath('messages.0.text', 'What time did you first notice this issue?');

        $this->assertDatabaseHas('info_requests', [
            'report_id' => $report->id,
            'requested_by' => $this->staff->id,
            'question' => 'What time did you first notice this issue?',
        ]);
        $this->assertDatabaseHas('status_logs', [
            'report_id' => $report->id,
            'to_status' => ReportStatus::NEEDS_INFO,
        ]);
        $this->assertSame(ReportStatus::NEEDS_INFO, $report->fresh()->status);
    }

    public function test_info_thread_endpoint_returns_messages_and_marks_seen(): void
    {
        $report = $this->createReport('BRG-THRD01');
        $infoRequest = InfoRequest::query()->create([
            'report_id' => $report->id,
            'requested_by' => $this->staff->id,
            'question' => 'Where exactly is the garbage pile?',
            'response' => 'Beside the barangay hall gate.',
            'responded_at' => now()->subMinutes(5),
        ]);

        // Without seen=1 the reply stays unread for the session marker.
        $this->actingAs($this->staff)
            ->getJson(route('admin.reports.info-requests.index', $report).'?seen=1')
            ->assertOk()
            ->assertJsonPath('report.tracking_code', $report->tracking_code)
            ->assertJsonPath('summary.count', 1)
            ->assertJsonPath('summary.unread', 0)
            ->assertJsonPath('messages.0.id', 'q'.$infoRequest->id)
            ->assertJsonPath('messages.0.sender', 'staff')
            ->assertJsonPath('messages.1.id', 'r'.$infoRequest->id)
            ->assertJsonPath('messages.1.sender', 'resident')
            ->assertJsonPath('messages.1.text', 'Beside the barangay hall gate.');
    }

    public function test_bulk_actions_assign_and_change_status(): void
    {
        $first = $this->createReport('BRG-BULK01');
        $second = $this->createReport('BRG-BULK02');

        $this->actingAs($this->staff)
            ->post(route('admin.reports.bulk-action', ['action' => 'assign_me']), [
                'report_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($this->staff->id, $first->fresh()->assigned_to);
        $this->assertSame($this->staff->id, $second->fresh()->assigned_to);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.bulk-action', ['action' => 'status']), [
                'report_ids' => [$first->id],
                'status' => 'under_review',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ReportStatus::UNDER_REVIEW, $first->fresh()->status);

        // Invalid transitions are skipped per row and reported, not thrown.
        $closed = $this->createReport('BRG-BULK03', ['status' => ReportStatus::RESOLVED]);
        $this->actingAs($this->staff)
            ->post(route('admin.reports.bulk-action', ['action' => 'status']), [
                'report_ids' => [$closed->id],
                'status' => 'under_review',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'skipped'));

        $this->assertSame(ReportStatus::RESOLVED, $closed->fresh()->status);
    }

    public function test_export_downloads_csv_of_current_filters(): void
    {
        $keep = $this->createReport('BRG-CSV001');
        $dropCategory = Category::create([
            'name' => 'Drainage',
            'slug' => 'drainage',
            'is_active' => true,
        ]);
        $drop = $this->createReport('BRG-CSV002', ['category_id' => $dropCategory->id]);

        $response = $this->actingAs($this->staff)
            ->get(route('admin.reports.export', ['category_id' => $this->category->id]))
            ->assertOk();

        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString($keep->tracking_code, $content);
        $this->assertStringContainsString('Tracking code', $content);
        $this->assertStringNotContainsString($drop->tracking_code, $content);
    }

    public function test_queue_page_requires_staff_authentication(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
    }
}
