<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportAiResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffJurisdictionAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->staff->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
        ]);

        $this->category = Category::query()->create([
            'name' => 'Drainage',
            'slug' => 'drainage',
            'is_active' => true,
        ]);
    }

    private function report(): Report
    {
        $report = Report::factory()->create([
            'status' => 'submitted',
            'category_id' => $this->category->id,
            'urgency' => 'Low',
            'reviewed_at' => null,
            'reviewed_by' => null,
            'resident_purok_id' => Purok::factory()->create()->id,
            'resident_landmark' => 'Resident landmark',
        ]);

        ReportAiResult::create([
            'report_id' => $report->id,
            'suggested_category' => $this->category->slug,
            'suggested_urgency' => 'Low',
            'summary' => 'Summary',
            'confidence' => 0.9,
        ]);

        return $report;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'intent' => 'save',
            'category_id' => $this->category->id,
            'urgency' => 'Low',
            'staff_description' => 'Summary',
            'jurisdiction_status' => 'within',
        ], $overrides);
    }

    public function test_within_assessment_is_saved_with_metadata(): void
    {
        $report = $this->report();
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.review', $report), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('within', $report->jurisdiction_status);
        $this->assertSame($this->staff->id, $report->jurisdiction_assessed_by);
        $this->assertNotNull($report->jurisdiction_assessed_at);
    }

    public function test_outside_requires_justification(): void
    {
        $report = $this->report();
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.review', $report), $this->payload(['jurisdiction_status' => 'outside']))
            ->assertRedirect()
            ->assertSessionHasErrors('review');

        $report->refresh();
        $this->assertNull($report->jurisdiction_status);
    }

    public function test_outside_with_justification_is_saved(): void
    {
        $report = $this->report();
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.review', $report), $this->payload([
                'jurisdiction_status' => 'outside',
                'jurisdiction_note' => 'Outside provisional boundary but involves Calarian resident.',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('outside', $report->jurisdiction_status);
        $this->assertSame('Outside provisional boundary but involves Calarian resident.', $report->jurisdiction_note);
    }

    public function test_uncertain_requires_justification(): void
    {
        $report = $this->report();
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.review', $report), $this->payload(['jurisdiction_status' => 'uncertain']))
            ->assertRedirect()
            ->assertSessionHasErrors('review');
    }

    public function test_invalid_jurisdiction_value_is_rejected(): void
    {
        $report = $this->report();
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.review', $report), $this->payload(['jurisdiction_status' => 'maybe']))
            ->assertRedirect()
            ->assertSessionHasErrors('jurisdiction_status');
    }

    public function test_original_resident_information_is_preserved(): void
    {
        $report = $this->report();
        $originalLat = $report->lat;
        $originalLng = $report->lng;
        $originalResidentPurok = $report->resident_purok_id;
        $originalResidentLandmark = $report->resident_landmark;

        $this->actingAs($this->staff)
            ->patch(route('admin.reports.review', $report), $this->payload(['purok_id' => Purok::factory()->create()->id, 'landmark' => 'Staff landmark']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame($originalLat, $report->lat);
        $this->assertSame($originalLng, $report->lng);
        $this->assertSame($originalResidentPurok, $report->resident_purok_id);
        $this->assertSame($originalResidentLandmark, $report->resident_landmark);
        $this->assertSame('Staff landmark', $report->landmark);
    }

    public function test_jurisdiction_change_creates_audit_log(): void
    {
        $report = $this->report();
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.review', $report), $this->payload([
                'jurisdiction_status' => 'outside',
                'jurisdiction_note' => 'Needs referral.',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $audit = AuditLog::query()->where('model_id', $report->id)->latest('id')->first();
        $this->assertNotNull($audit);
        $this->assertSame($this->staff->id, $audit->user_id);
        $this->assertSame('outside', $audit->new_values['jurisdiction_status'] ?? null);
    }

    public function test_unauthenticated_cannot_review(): void
    {
        $report = $this->report();
        $this->patch(route('admin.reports.review', $report), $this->payload())
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_review(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $report = $this->report();
        $this->actingAs($user)
            ->patch(route('admin.reports.review', $report), $this->payload())
            ->assertForbidden();
    }
}
