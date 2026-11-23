<?php

namespace Tests\Feature\CaseActions;

use App\Models\BarangayCase;
use App\Models\CaseParty;
use App\Models\EscalationDestination;
use App\Models\Party;
use App\Models\Personnel;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscalationTest extends TestCase
{
    use RefreshDatabase;

    public function test_escalation_create_page_renders_with_soft_warning_when_deadline_not_elapsed(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
            'filed_on' => now()->toDateString(), // filed today -> 1 working day elapsed, < 15
        ]);

        $response = $this->actingAs($admin)->get(route('admin.cases.escalations.create', $case));

        $response->assertOk()
            ->assertSee('Escalate Case & Issue CFA', false)
            ->assertSee('Conciliation Period Not Completed')
            ->assertSee('Officials decide whether to proceed');
    }

    public function test_case_can_be_escalated_and_creates_cfa_record(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);

        $party = Party::factory()->create();
        $complainant = CaseParty::create([
            'case_id' => $case->id,
            'party_id' => $party->id,
            'role' => 'complainant',
        ]);

        $destination = EscalationDestination::factory()->create(['name' => 'MTCC Branch 2']);
        $personnel = Personnel::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.cases.escalations.store', $case), [
            'requested_by_case_party_id' => $complainant->id,
            'requested_on' => now()->toDateString(),
            'escalation_destination_id' => $destination->id,
            'signed_by_personnel_id' => $personnel->id,
            'reason' => 'Parties reached an impasse after 3 conferences. Complainant petitions for court action.',
            'override_note' => 'Approved by Punong Barangay.',
        ]);

        $response->assertRedirect(route('admin.cases.show', ['case' => $case, 'tab' => 'escalation']));

        $case->refresh();
        $this->assertSame(CaseStatus::ESCALATED, $case->status);

        $this->assertDatabaseHas('case_escalations', [
            'case_id' => $case->id,
            'requested_by_case_party_id' => $complainant->id,
            'escalation_destination_id' => $destination->id,
            'reason' => 'Parties reached an impasse after 3 conferences. Complainant petitions for court action.',
        ]);
    }

    public function test_cfa_and_endorsement_letter_print_views_render(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::ESCALATED,
        ]);

        $party = Party::factory()->create(['full_name' => 'Maria Complainant']);
        $complainant = CaseParty::create([
            'case_id' => $case->id,
            'party_id' => $party->id,
            'role' => 'complainant',
        ]);

        $destination = EscalationDestination::factory()->create(['name' => 'Office of the City Prosecutor']);

        $case->escalations()->create([
            'requested_by_case_party_id' => $complainant->id,
            'requested_on' => now()->toDateString(),
            'escalation_destination_id' => $destination->id,
            'escalated_by' => $admin->id,
            'cfa_number' => 'CFA-2026-0099',
            'reason' => 'Mediation failed.',
            'summons_count_at_time' => 2,
            'escalated_at' => now(),
        ]);

        // CFA print
        $cfaResponse = $this->actingAs($admin)->get(route('admin.cases.escalations.cfa', $case));
        $cfaResponse->assertOk()
            ->assertSee('CERTIFICATION TO FILE ACTION')
            ->assertSee('CFA-2026-0099')
            ->assertSee('Maria Complainant');

        // Endorsement letter print
        $letterResponse = $this->actingAs($admin)->get(route('admin.cases.escalations.letter', $case));
        $letterResponse->assertOk()
            ->assertSee('ENDORSEMENT OF CASE')
            ->assertSee('OFFICE OF THE CITY PROSECUTOR');
    }
}
