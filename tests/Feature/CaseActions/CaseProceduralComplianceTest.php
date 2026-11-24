<?php

namespace Tests\Feature\CaseActions;

use App\Models\BarangayCase;
use App\Models\Hearing;
use App\Models\User;
use App\Services\OverdueService;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseProceduralComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->admin()->create();
    }

    public function test_pre_summons_eligibility_screening_checklist_can_be_saved(): void
    {
        $case = BarangayCase::factory()->create([
            'status' => CaseStatus::FILED,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.cases.eligibility', $case), [
            'same_barangay' => '1',
            'kp_matter' => '1',
            'non_confidential' => '1',
            'residency_verified' => '1',
        ]);

        $response->assertRedirect(route('admin.cases.show', $case));

        $case->refresh();
        $this->assertNotNull($case->eligibility_checklist);
        $this->assertTrue($case->eligibility_checklist['same_barangay']);
        $this->assertTrue($case->eligibility_checklist['kp_matter']);
        $this->assertTrue($case->eligibility_checklist['non_confidential']);
        $this->assertTrue($case->eligibility_checklist['residency_verified']);

        // Assert rendered card displays 'Eligible'
        $viewResponse = $this->actingAs($this->user)->get(route('admin.cases.show', $case));
        $viewResponse->assertOk()
            ->assertSeeText('Pre-Summons KP Eligibility Screening')
            ->assertSeeText('Eligible');
    }

    public function test_statutory_15_working_day_clock_starts_from_first_meeting_on(): void
    {
        // Case filed 20 days ago
        $case = BarangayCase::factory()->create([
            'status' => CaseStatus::IN_MEDIATION,
            'filed_on' => now()->subDays(20),
            'first_meeting_on' => now()->subDays(2)->toDateString(),
        ]);

        $overdueService = app(OverdueService::class);
        $result = $overdueService->forCase($case);

        // Even though filed 20 days ago, mediation started 2 days ago via first_meeting_on
        $this->assertFalse($result['is_overdue']);
        $this->assertLessThanOrEqual(3, $result['day']);
        $this->assertEquals('mediation 15 working days', $result['rule']);

        // Can update first_meeting_on via dedicated route
        $response = $this->actingAs($this->user)->post(route('admin.cases.first-meeting', $case), [
            'first_meeting_on' => now()->subDays(1)->toDateString(),
        ]);
        $response->assertRedirect(route('admin.cases.show', $case));
        $this->assertEquals(now()->subDays(1)->toDateString(), $case->fresh()->first_meeting_on->toDateString());
    }

    public function test_hearing_outcome_records_attendance_and_bars_respondent_counterclaim(): void
    {
        $case = BarangayCase::factory()->create([
            'status' => CaseStatus::IN_MEDIATION,
            'first_meeting_on' => null,
        ]);
        $hearing = Hearing::factory()->create([
            'case_id' => $case->id,
            'scheduled_at' => now()->subHours(2),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.cases.hearings.outcome', [$case, $hearing]), [
            'status' => 'held',
            'complainant_attendance' => 'appeared',
            'respondent_attendance' => 'failed_to_appear',
            'outcome_note' => 'Respondent failed to appear without justification despite valid summons.',
        ]);

        $response->assertRedirect(route('admin.cases.show', ['case' => $case, 'tab' => 'hearings']));

        $hearing->refresh();
        $this->assertEquals('held', $hearing->status);
        $this->assertEquals('appeared', $hearing->complainant_attendance);
        $this->assertEquals('failed_to_appear', $hearing->respondent_attendance);
        $this->assertTrue($hearing->respondent_barred_counterclaim);

        // Auto-recorded first_meeting_on because complainant appeared and hearing was held
        $this->assertNotNull($case->fresh()->first_meeting_on);

        // Check show view displays barred counterclaim notice
        $showResponse = $this->actingAs($this->user)->get(route('admin.cases.show', ['case' => $case, 'tab' => 'hearings']));
        $showResponse->assertOk()
            ->assertSeeText('Respondent Counterclaim Barred (Sec. 412(b))');
    }

    public function test_escalation_view_displays_warning_when_pangkat_not_constituted(): void
    {
        $case = BarangayCase::factory()->create([
            'status' => CaseStatus::UNSETTLED,
            'endorsed_to_pangkat_on' => null,
            'pangkat_chairman_personnel_id' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.cases.escalations.create', $case));

        $response->assertOk()
            ->assertSeeText('Procedural Warning: Pangkat Conciliation Not Recorded')
            ->assertSeeText('Under RA 7160 Sec. 410(b)');
    }
}
