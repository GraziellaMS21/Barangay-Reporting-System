<?php

namespace Tests\Feature\CaseActions;

use App\Models\BarangayCase;
use App\Models\CaseParty;
use App\Models\Summons;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummonsCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_workspace_shows_non_voided_summons_count_for_record_keeping(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $respondent = CaseParty::factory()->for($case, 'case')->create([
            'role' => 'respondent',
        ]);

        foreach ([1, 2] as $sequenceNo) {
            Summons::factory()->create([
                'case_id' => $case->id,
                'respondent_case_party_id' => $respondent->id,
                'sequence_no' => $sequenceNo,
            ]);
        }
        Summons::factory()->create([
            'case_id' => $case->id,
            'respondent_case_party_id' => $respondent->id,
            'sequence_no' => 3,
            'voided_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.cases.show', $case))
            ->assertOk()
            ->assertSee('2 summonses issued (record only)')
            ->assertSee('Summons are issued when a hearing is scheduled. Go to Hearings.')
            ->assertDontSee('Advisory mark');
    }

    public function test_issuing_a_summons_via_unified_workflow_updates_count_without_escalating_case(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
            'complaint_signed_received_at' => now(),
        ]);
        $respondent = CaseParty::factory()->for($case, 'case')->create([
            'role' => 'respondent',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.cases.workflow.schedule-and-issue-summons', $case), [
                'respondent_case_party_id' => $respondent->id,
                'scheduled_at' => now()->addWeek()->setTime(9, 0)->format('Y-m-d\TH:i'),
                'venue' => 'Barangay Hall Mediation Room',
            ]);

        $response->assertRedirect(route('admin.cases.show', $case))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('summons', [
            'case_id' => $case->id,
            'respondent_case_party_id' => $respondent->id,
            'sequence_no' => 1,
            'issued_by' => $admin->id,
        ]);
        $this->assertSame(1, $case->activeSummons()->count());
        $this->assertSame(CaseStatus::IN_MEDIATION, $case->fresh()->status);
    }
}
