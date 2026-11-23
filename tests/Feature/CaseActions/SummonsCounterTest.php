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
            ->assertSee('2 issued summonses')
            ->assertSee('numbered sequentially for information and record-keeping only')
            ->assertDontSee('Advisory mark');
    }

    public function test_admin_can_open_summons_form_for_an_active_complaint(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $respondent = CaseParty::factory()->for($case, 'case')->create([
            'role' => 'respondent',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.cases.summons.create', $case))
            ->assertOk()
            ->assertSee('Summons details')
            ->assertSee('Hearing date and time')
            ->assertSee($respondent->party->full_name);
    }

    public function test_issuing_a_summons_updates_the_count_without_escalating_the_case(): void
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

        $response = $this->actingAs($admin)
            ->post(route('admin.cases.summons.store', $case), [
                'respondent_case_party_id' => $respondent->id,
                'hearing_at' => now()->addWeek()->format('Y-m-d H:i:s'),
                'venue' => 'Barangay Hall Mediation Room',
            ]);

        $response->assertRedirect(route('admin.cases.show', $case))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('summons', [
            'case_id' => $case->id,
            'respondent_case_party_id' => $respondent->id,
            'sequence_no' => 3,
            'issued_by' => $admin->id,
        ]);
        $this->assertSame(3, $case->activeSummons()->count());
        $this->assertSame(CaseStatus::IN_MEDIATION, $case->fresh()->status);

        $this->get(route('admin.cases.show', $case))
            ->assertOk()
            ->assertSee('3 issued summonses')
            ->assertDontSee('3 of 3');
    }

    public function test_summons_can_continue_beyond_three_for_record_keeping(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $respondent = CaseParty::factory()->for($case, 'case')->create([
            'role' => 'respondent',
        ]);

        foreach ([1, 2, 3] as $sequenceNo) {
            Summons::factory()->create([
                'case_id' => $case->id,
                'respondent_case_party_id' => $respondent->id,
                'sequence_no' => $sequenceNo,
            ]);
        }

        $this->actingAs($admin)
            ->post(route('admin.cases.summons.store', $case), [
                'respondent_case_party_id' => $respondent->id,
                'hearing_at' => now()->addWeek()->format('Y-m-d H:i:s'),
                'venue' => 'Barangay Hall Mediation Room',
            ])
            ->assertRedirect(route('admin.cases.show', $case))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('summons', [
            'case_id' => $case->id,
            'respondent_case_party_id' => $respondent->id,
            'sequence_no' => 4,
            'issued_by' => $admin->id,
        ]);
        $this->assertSame(4, $case->activeSummons()->count());
        $this->assertSame(CaseStatus::IN_MEDIATION, $case->fresh()->status);
    }

    public function test_summons_cannot_be_issued_to_a_respondent_on_another_case(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $otherCase = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $foreignRespondent = CaseParty::factory()->for($otherCase, 'case')->create([
            'role' => 'respondent',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.cases.summons.store', $case), [
                'respondent_case_party_id' => $foreignRespondent->id,
                'hearing_at' => now()->addWeek()->format('Y-m-d H:i:s'),
                'venue' => 'Barangay Hall Mediation Room',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('respondent_case_party_id');

        $this->assertDatabaseCount('summons', 0);
    }

    public function test_blotter_only_cases_cannot_issue_summons(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'blotter',
            'status' => CaseStatus::FILED,
        ]);
        $respondent = CaseParty::factory()->for($case, 'case')->create([
            'role' => 'respondent',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.cases.summons.store', $case), [
                'respondent_case_party_id' => $respondent->id,
                'hearing_at' => now()->addWeek()->format('Y-m-d H:i:s'),
                'venue' => 'Barangay Hall Mediation Room',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('case');

        $this->assertDatabaseCount('summons', 0);
    }

    public function test_staff_without_summons_permission_cannot_view_the_summons_counter(): void
    {
        $staff = User::factory()->staff()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);

        $this->actingAs($staff)
            ->get(route('admin.cases.summons.create', $case))
            ->assertForbidden();
    }
}
