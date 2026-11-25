<?php

namespace Tests\Feature\Cases;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\Hearing;
use App\Models\Permission;
use App\Models\Summons;
use App\Models\User;
use App\Support\CaseStatus;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseGuidedWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected CaseType $caseType;

    protected FiscalYear $fiscalYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RequiredDataSeeder::class);

        $this->fiscalYear = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['label' => 'FY 2026', 'is_open' => true, 'is_current' => true, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']
        );

        $this->caseType = CaseType::firstOrCreate(
            ['code' => 'DISPUTE_TEST'],
            ['name' => 'Neighborhood Dispute', 'slug' => 'dispute-test', 'is_active' => true, 'requires_screening' => true]
        );

        $this->staffUser = User::factory()->staff()->create([
            'is_active' => true,
        ]);

        $permissions = Permission::query()->pluck('id');
        $this->staffUser->permissions()->sync($permissions);
    }

    public function test_case_show_renders_next_step_panel_and_stepper(): void
    {
        $case = BarangayCase::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'case_type_id' => $this->caseType->id,
            'status' => CaseStatus::FILED,
            'complaint_signed_received_at' => null,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('admin.cases.show', $case));

        $response->assertOk();
        $response->assertSee('Action Required');
        $response->assertSee('Print the complaint for in-person signing');
        $response->assertSee('1. Review');
        $response->assertSee('2. Docket');
        $response->assertSee('3. Mediation');
    }

    public function test_milestone_1_marks_signed_complaint_received(): void
    {
        $case = BarangayCase::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'case_type_id' => $this->caseType->id,
            'status' => CaseStatus::FILED,
            'complaint_signed_received_at' => null,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.mark-signed-complaint', $case), [
                'signed_at' => date('Y-m-d'),
                'received_by' => 'Punong Barangay',
                'notes' => 'Complainant verified in person.',
            ]);

        $response->assertRedirect(route('admin.cases.show', $case));
        $this->assertNotNull($case->fresh()->complaint_signed_received_at);
        $this->assertEquals('Punong Barangay', $case->fresh()->complaint_signed_received_by);
    }

    public function test_merged_step_2_schedules_hearing_and_issues_summons_in_one_chain(): void
    {
        $case = BarangayCase::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'case_type_id' => $this->caseType->id,
            'status' => CaseStatus::FILED,
            'complaint_signed_received_at' => now(),
            'first_meeting_on' => null,
        ]);

        $futureDate = now()->addDays(3)->setTime(9, 0)->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.schedule-and-issue-summons', $case), [
                'scheduled_at' => $futureDate,
                'venue' => 'Barangay Calarian Hall',
                'notes' => 'Deliver summons immediately.',
                'notify_channels' => 1,
            ]);

        $response->assertRedirect(route('admin.cases.show', $case));

        $hearing = Hearing::where('case_id', $case->id)->first();
        $summons = Summons::where('case_id', $case->id)->first();

        $this->assertNotNull($hearing);
        $this->assertNotNull($summons);
        $this->assertEquals($hearing->id, $summons->hearing_id);
        $this->assertEquals($summons->id, $hearing->summons_id);
        $this->assertEquals(1, $hearing->sequence_no);
        $this->assertEquals(1, $summons->sequence_no);

        $freshCase = $case->fresh();
        $this->assertEquals(CaseStatus::IN_MEDIATION, $freshCase->status);

        // Next Step panel shows Record Service as the single primary button
        $panelResponse = $this->actingAs($this->staffUser)
            ->get(route('admin.cases.show', $case));
        $panelResponse->assertOk();
        $panelResponse->assertSee('Print and serve Summons #1');
        $panelResponse->assertSee('Record Service');
        $panelResponse->assertSee('Record service first');
    }

    public function test_outcome_requires_service_or_skip_override_and_no_show_keeps_in_mediation(): void
    {
        $case = BarangayCase::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'case_type_id' => $this->caseType->id,
            'status' => CaseStatus::FILED,
            'complaint_signed_received_at' => now(),
        ]);

        $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.schedule-and-issue-summons', $case), [
                'scheduled_at' => now()->addDays(2)->setTime(9, 0)->format('Y-m-d\TH:i'),
                'venue' => 'Barangay Hall',
            ]);

        $hearing = $case->hearings()->first();
        $summons = $case->summons()->first();

        // Attempting to record outcome before service fails
        $blocked = $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.record-hearing-outcome', $case), [
                'hearing_id' => $hearing->id,
                'outcome' => 'respondent_absent',
            ]);
        $blocked->assertSessionHas('error');

        // Record service via Personal
        $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.record-summons-service', $case), [
                'summons_id' => $summons->id,
                'served_at' => now()->toDateString(),
                'service_method' => 'personal',
                'served_by' => 'Tanod Juan',
            ])->assertRedirect(route('admin.cases.show', $case));

        // Now record respondent_absent -> stays IN_MEDIATION and prompts Schedule Next Hearing
        $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.record-hearing-outcome', $case), [
                'hearing_id' => $hearing->id,
                'outcome' => 'respondent_absent',
                'complainant_present' => 1,
                'notes' => 'Respondent did not appear.',
            ])->assertRedirect(route('admin.cases.show', $case));

        $this->assertEquals(CaseStatus::IN_MEDIATION, $case->fresh()->status);

        $view = $this->actingAs($this->staffUser)->get(route('admin.cases.show', $case));
        $view->assertSee('Schedule the next hearing (Summons #2)');
        $view->assertSee('Schedule Next Hearing');
    }

    public function test_skip_service_override_logs_audit_and_unlocks_outcome(): void
    {
        $case = BarangayCase::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'case_type_id' => $this->caseType->id,
            'status' => CaseStatus::FILED,
            'complaint_signed_received_at' => now(),
        ]);

        $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.schedule-and-issue-summons', $case), [
                'scheduled_at' => now()->addDays(2)->setTime(9, 0)->format('Y-m-d\TH:i'),
                'venue' => 'Barangay Hall',
            ]);

        $summons = $case->summons()->first();

        $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.skip-summons-service', $case), [
                'summons_id' => $summons->id,
                'reason' => 'Both parties walked into the hall together.',
            ])->assertRedirect(route('admin.cases.show', $case));

        $this->assertEquals('Both parties walked into the hall together.', $summons->fresh()->service_skipped_reason);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'case.summons_service_skipped',
        ]);
    }

    public function test_reschedule_marks_old_pair_superseded_and_day_15_shows_endorse_to_pangkat(): void
    {
        $case = BarangayCase::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'case_type_id' => $this->caseType->id,
            'status' => CaseStatus::IN_MEDIATION,
            'complaint_signed_received_at' => now()->subDays(30),
        ]);

        // Issue initial summons in the future, then reschedule
        $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.schedule-and-issue-summons', $case), [
                'scheduled_at' => now()->nextWeekday()->addDays(2)->setTime(9, 0)->format('Y-m-d\TH:i'),
                'venue' => 'Barangay Hall',
            ]);

        $firstHearing = $case->hearings()->first();
        $firstSummons = $case->activeSummons()->first();

        $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.schedule-and-issue-summons', $case), [
                'reschedule_hearing_id' => $firstHearing->id,
                'scheduled_at' => now()->nextWeekday()->addDays(4)->setTime(9, 0)->format('Y-m-d\TH:i'),
                'venue' => 'Barangay Hall Room 2',
                'reason' => 'Respondent requested postponement for medical treatment.',
            ])->assertRedirect(route('admin.cases.show', $case));

        $this->assertNotNull($firstHearing->fresh()->superseded_at);
        $this->assertNotNull($firstSummons->fresh()->superseded_at);
        $this->assertEquals(2, $case->hearings()->count());
        $this->assertEquals(1, $case->activeHearings()->count());

        // Move earliest hearing 25 working days back to simulate >15 working days elapsed
        $firstHearing->update(['scheduled_at' => now()->subDays(35)]);

        $overdueView = $this->actingAs($this->staffUser)->get(route('admin.cases.show', $case));
        $overdueView->assertOk();
        $overdueView->assertSee('15 working days reached without settlement');
        $overdueView->assertSee('Endorse to Pangkat');
    }
}
