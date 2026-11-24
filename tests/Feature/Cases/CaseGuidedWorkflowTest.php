<?php

namespace Tests\Feature\Cases;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\Permission;
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
        $response->assertSee('Action Required · Step 1');
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

    public function test_merged_step_2_schedules_hearing_and_issues_summons_in_one_action(): void
    {
        $case = BarangayCase::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'case_type_id' => $this->caseType->id,
            'status' => CaseStatus::FILED,
            'complaint_signed_received_at' => now(),
            'first_meeting_on' => null,
        ]);

        $futureDate = now()->addDays(3)->setTime(10, 0)->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.schedule-and-issue-summons', $case), [
                'scheduled_at' => $futureDate,
                'venue' => 'Barangay Calarian Hall',
                'notes' => 'Deliver summons immediately.',
                'notify_channels' => 1,
            ]);

        $response->assertRedirect(route('admin.cases.show', $case));

        // 1. Hearing record exists
        $this->assertDatabaseHas('hearings', [
            'case_id' => $case->id,
            'venue' => 'Barangay Calarian Hall',
        ]);

        // 2. Summons record exists with issued_on set
        $this->assertDatabaseHas('summons', [
            'case_id' => $case->id,
            'venue' => 'Barangay Calarian Hall',
        ]);

        $freshCase = $case->fresh();
        $this->assertEquals(CaseStatus::IN_MEDIATION, $freshCase->status);
        $this->assertNotNull($freshCase->notices_issued_at);
        // Clock has NOT started yet before meeting takes place
        $this->assertNull($freshCase->mediation_started_at);

        // Verification: Immediately shows Record Hearing Outcome, Record Service, and Print buttons
        $panelResponse = $this->actingAs($this->staffUser)
            ->get(route('admin.cases.show', $case));
        $panelResponse->assertOk();
        $panelResponse->assertSee('Record Hearing Outcome');
        $panelResponse->assertSee('Record Service');
        $panelResponse->assertSee('Print Summons (Form 9) - Respondent');
        $panelResponse->assertSee('Print Hearing Notice - Complainant');
    }

    public function test_reschedule_hearing_marks_previous_summons_as_superseded(): void
    {
        $case = BarangayCase::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'case_type_id' => $this->caseType->id,
            'status' => CaseStatus::IN_MEDIATION,
            'complaint_signed_received_at' => now(),
            'first_meeting_on' => now()->toDateString(),
            'summons_served_at' => now(),
        ]);

        // Issue initial summons
        $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.schedule-and-issue-summons', $case), [
                'scheduled_at' => now()->nextWeekday()->addDays(2)->setTime(10, 0)->format('Y-m-d\TH:i'),
                'venue' => 'Barangay Hall',
            ]);

        $firstSummons = $case->activeSummons()->first();
        $this->assertNotNull($firstSummons);

        // Reschedule
        $newDate = now()->nextWeekday()->addDays(4)->setTime(14, 0)->format('Y-m-d\TH:i');
        $response = $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.reschedule-hearing', $case), [
                'scheduled_at' => $newDate,
                'venue' => 'Barangay Hall Mediation Room',
                'reason' => 'Respondent was out of town for medical treatment.',
            ]);

        $response->assertRedirect(route('admin.cases.show', $case));

        // Previous summons superseded
        $this->assertNotNull($firstSummons->fresh()->superseded_at);

        // New summons created
        $newSummons = $case->activeSummons()->latest()->first();
        $this->assertNotEquals($firstSummons->id, $newSummons->id);
        $this->assertNull($newSummons->superseded_at);

        // Service milestone reset
        $this->assertNull($case->fresh()->summons_served_at);
    }

    public function test_clock_starts_when_meeting_held_and_settlement_flow(): void
    {
        $case = BarangayCase::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'case_type_id' => $this->caseType->id,
            'status' => CaseStatus::IN_MEDIATION,
            'complaint_signed_received_at' => now(),
            'first_meeting_on' => now()->toDateString(),
            'notices_issued_at' => now(),
            'summons_served_at' => now(),
        ]);

        // Record hearing outcome with both parties present -> starts clock
        $responseOutcome = $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.record-hearing-outcome', $case), [
                'outcome' => 'settled',
                'both_parties_present' => 1,
                'notes' => 'Parties met and agreed on terms.',
            ]);
        $responseOutcome->assertRedirect(route('admin.cases.show', $case));

        $freshCase = $case->fresh();
        $this->assertNotNull($freshCase->mediation_started_at);

        // Record signed settlement
        $responseSettlement = $this->actingAs($this->staffUser)
            ->post(route('admin.cases.workflow.mark-signed-settlement', $case), [
                'settled_on' => date('Y-m-d'),
                'terms' => 'Both parties agree to cease loud karaoke past 10 PM and maintain peace.',
            ]);
        $responseSettlement->assertRedirect(route('admin.cases.show', $case));

        $this->assertNotNull($freshCase->fresh()->settled_on);
        $this->assertEquals(CaseStatus::SETTLED, $freshCase->fresh()->status);
    }
}
