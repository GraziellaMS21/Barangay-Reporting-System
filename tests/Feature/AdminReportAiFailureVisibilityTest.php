<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportAiFailureVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function staffWithReportsView(): User
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
        ]);

        return $staff;
    }

    public function test_queue_displays_manual_screening_badge_when_ai_status_is_failed(): void
    {
        $staff = $this->staffWithReportsView();

        $failedReport = Report::factory()->create([
            'tracking_code' => 'BRG-FAIL001',
            'ai_status' => 'failed',
            'description' => 'Report with failed AI screening',
        ]);

        $doneReport = Report::factory()->create([
            'tracking_code' => 'BRG-DONE001',
            'ai_status' => 'done',
            'description' => 'Report with successful AI screening',
        ]);

        $response = $this->actingAs($staff)
            ->get(route('admin.reports.index'));

        $response->assertOk()
            ->assertSee('BRG-FAIL001')
            ->assertSee('BRG-DONE001')
            ->assertSee('Manual screening')
            ->assertSee('AI screening unavailable — manual review required.');
    }

    public function test_queue_does_not_display_manual_screening_badge_when_ai_status_is_not_failed(): void
    {
        $staff = $this->staffWithReportsView();

        Report::factory()->create([
            'tracking_code' => 'BRG-DONE002',
            'ai_status' => 'done',
            'description' => 'Report with successful AI screening',
        ]);

        $response = $this->actingAs($staff)
            ->get(route('admin.reports.index'));

        $response->assertOk()
            ->assertSee('BRG-DONE002')
            ->assertDontSee('AI screening unavailable — manual review required.');
    }

    public function test_queue_can_filter_by_ai_status_failed(): void
    {
        $staff = $this->staffWithReportsView();

        $failedReport = Report::factory()->create([
            'tracking_code' => 'BRG-FAIL003',
            'ai_status' => 'failed',
        ]);

        $doneReport = Report::factory()->create([
            'tracking_code' => 'BRG-DONE003',
            'ai_status' => 'done',
        ]);

        $pendingReport = Report::factory()->create([
            'tracking_code' => 'BRG-PEND003',
            'ai_status' => 'pending',
        ]);

        $response = $this->actingAs($staff)
            ->get(route('admin.reports.index', ['ai_status' => 'failed']));

        $response->assertOk()
            ->assertSee('BRG-FAIL003')
            ->assertDontSee('BRG-DONE003')
            ->assertDontSee('BRG-PEND003');
    }

    public function test_queue_can_filter_by_ai_status_done(): void
    {
        $staff = $this->staffWithReportsView();

        $failedReport = Report::factory()->create([
            'tracking_code' => 'BRG-FAIL004',
            'ai_status' => 'failed',
        ]);

        $doneReport = Report::factory()->create([
            'tracking_code' => 'BRG-DONE004',
            'ai_status' => 'done',
        ]);

        $response = $this->actingAs($staff)
            ->get(route('admin.reports.index', ['ai_status' => 'done']));

        $response->assertOk()
            ->assertSee('BRG-DONE004')
            ->assertDontSee('BRG-FAIL004');
    }

    public function test_queue_rejects_invalid_ai_status_filter_safely(): void
    {
        $staff = $this->staffWithReportsView();

        $response = $this->actingAs($staff)
            ->get(route('admin.reports.index', ['ai_status' => 'malicious_injection']));

        $response->assertSessionHasErrors(['ai_status']);
    }

    public function test_queue_pagination_preserves_ai_status_parameter(): void
    {
        $staff = $this->staffWithReportsView();

        Report::factory()->count(20)->create([
            'ai_status' => 'failed',
        ]);

        $response = $this->actingAs($staff)
            ->get(route('admin.reports.index', ['ai_status' => 'failed']));

        $response->assertOk()
            ->assertSee('ai_status=failed');
    }

    public function test_staff_can_review_and_classify_report_with_failed_ai_status(): void
    {
        $staff = $this->staffWithReportsView();
        $category = Category::query()->create([
            'name' => 'Road damage',
            'slug' => 'road-damage',
            'is_active' => true,
        ]);

        $report = Report::factory()->create([
            'tracking_code' => 'BRG-FAIL005',
            'ai_status' => 'failed',
            'status' => 'submitted',
            'category_id' => null,
            'urgency' => 'low',
            'reviewed_at' => null,
        ]);

        $showResponse = $this->actingAs($staff)
            ->get(route('admin.reports.show', $report));

        $showResponse->assertOk()
            ->assertSee('BRG-FAIL005');

        $reviewResponse = $this->actingAs($staff)
            ->patch(route('admin.reports.review', $report), [
                'intent' => 'save',
                'category_id' => $category->id,
                'urgency' => 'High',
                'staff_description' => 'Manual staff classification after AI failure.',
                'review_note' => 'Manually categorized by staff.',
            ]);

        $reviewResponse->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame($category->id, $report->category_id);
        $this->assertSame('High', $report->urgency);
        $this->assertNotNull($report->reviewed_at);
        $this->assertSame($staff->id, $report->reviewed_by);
        $this->assertSame('failed', $report->ai_status);
    }

    public function test_emergency_report_with_failed_ai_retains_visual_priority_and_emergency_styling(): void
    {
        $staff = $this->staffWithReportsView();

        $emergencyReport = Report::factory()->create([
            'tracking_code' => 'BRG-EMERG-FAIL',
            'ai_status' => 'failed',
            'is_emergency' => true,
            'status' => 'submitted',
            'urgency' => 'critical',
            'description' => 'Flash flooding near riverbank',
        ]);

        $response = $this->actingAs($staff)
            ->get(route('admin.reports.index'));

        $response->assertOk()
            ->assertSee('BRG-EMERG-FAIL')
            ->assertSee('is-emergency-row')
            ->assertSee('Manual screening')
            ->assertSee('AI screening unavailable — manual review required.');
    }
}
