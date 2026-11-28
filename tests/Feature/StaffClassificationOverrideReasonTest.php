<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Report;
use App\Models\ReportAiResult;
use App\Models\StatusLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffClassificationOverrideReasonTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Category $aiCategory;

    private Category $residentCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->staff->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
        ]);

        $this->aiCategory = Category::query()->create([
            'name' => 'Road damage',
            'slug' => 'ai-road-damage',
            'is_active' => true,
        ]);
        $this->residentCategory = Category::query()->create([
            'name' => 'Drainage',
            'slug' => 'resident-drainage',
            'is_active' => true,
        ]);
    }

    private function reportWithAiSuggestion(): Report
    {
        $report = Report::factory()->create([
            'tracking_code' => 'BRG-OVR001',
            'status' => 'submitted',
            'ai_status' => 'done',
            'category_id' => $this->residentCategory->id,
            'urgency' => 'Low',
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);

        ReportAiResult::create([
            'report_id' => $report->id,
            'suggested_category' => $this->aiCategory->slug,
            'suggested_urgency' => 'High',
            'summary' => 'AI summary of the report.',
            'confidence' => 0.91,
        ]);

        return $report;
    }

    private function reviewPayload(array $overrides = []): array
    {
        return array_merge([
            'intent' => 'save',
            'category_id' => $this->residentCategory->id,
            'urgency' => 'High',
            'staff_description' => 'AI summary of the report.',
        ], $overrides);
    }

    public function test_overriding_ai_classification_without_reason_is_rejected(): void
    {
        $report = $this->reportWithAiSuggestion();

        $this->actingAs($this->staff)
            ->patch(route('admin.reports.review', $report), $this->reviewPayload())
            ->assertRedirect()
            ->assertSessionHasErrors('review');

        $report->refresh();
        $this->assertSame($this->residentCategory->id, $report->category_id);
        $this->assertNull($report->reviewed_at);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'staff_review',
            'model_id' => $report->id,
        ]);
    }

    public function test_overriding_ai_classification_with_reason_is_saved_and_audited(): void
    {
        $report = $this->reportWithAiSuggestion();

        $this->actingAs($this->staff)
            ->patch(route('admin.reports.review', $report), $this->reviewPayload([
                'override_reason' => 'Resident reported a blocked drainage canal, not road damage.',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame($this->residentCategory->id, $report->category_id);
        $this->assertNotNull($report->reviewed_at);
        $this->assertSame($this->staff->id, $report->reviewed_by);

        $audit = AuditLog::query()
            ->where('action', 'staff_review')
            ->where('model_id', $report->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($this->staff->id, $audit->user_id);
        $this->assertSame(
            'Resident reported a blocked drainage canal, not road damage.',
            $audit->new_values['override_reason'] ?? null
        );

        $statusLog = StatusLog::query()
            ->where('report_id', $report->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($statusLog);
        $this->assertStringContainsString(
            'Classification override reason: Resident reported a blocked drainage canal, not road damage.',
            (string) $statusLog->note
        );
    }

    public function test_accepting_ai_classification_does_not_require_a_reason(): void
    {
        $report = $this->reportWithAiSuggestion();

        $this->actingAs($this->staff)
            ->patch(route('admin.reports.review', $report), $this->reviewPayload([
                'category_id' => $this->aiCategory->id,
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame($this->aiCategory->id, $report->category_id);
        $this->assertNotNull($report->reviewed_at);
    }
}
