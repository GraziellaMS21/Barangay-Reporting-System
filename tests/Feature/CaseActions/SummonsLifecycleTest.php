<?php

namespace Tests\Feature\CaseActions;

use App\Models\BarangayCase;
use App\Models\CaseParty;
use App\Models\Party;
use App\Models\Personnel;
use App\Models\Summons;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummonsLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_result_can_be_recorded(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $party = Party::factory()->create();
        $respondent = CaseParty::create([
            'case_id' => $case->id,
            'party_id' => $party->id,
            'role' => 'respondent',
        ]);
        $personnel = Personnel::factory()->create();
        $summons = Summons::factory()->create([
            'case_id' => $case->id,
            'respondent_case_party_id' => $respondent->id,
            'service_result' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.cases.summons.service', [$case, $summons]), [
            'service_result' => 'received',
            'served_on' => now()->toDateString(),
            'served_by_personnel_id' => $personnel->id,
            'remarks' => 'Served in person at residence.',
        ]);

        $response->assertRedirect(route('admin.cases.show', $case));
        $this->assertDatabaseHas('summons', [
            'id' => $summons->id,
            'service_result' => 'received',
            'served_by_personnel_id' => $personnel->id,
            'remarks' => 'Served in person at residence.',
        ]);
    }

    public function test_attendance_can_be_recorded(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $party = Party::factory()->create();
        $respondent = CaseParty::create([
            'case_id' => $case->id,
            'party_id' => $party->id,
            'role' => 'respondent',
        ]);
        $summons = Summons::factory()->create([
            'case_id' => $case->id,
            'respondent_case_party_id' => $respondent->id,
            'attendance' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.cases.summons.attendance', [$case, $summons]), [
            'attendance' => 'appeared',
        ]);

        $response->assertRedirect(route('admin.cases.show', $case));
        $this->assertDatabaseHas('summons', [
            'id' => $summons->id,
            'attendance' => 'appeared',
        ]);
    }

    public function test_summons_can_be_voided_with_required_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $party = Party::factory()->create();
        $respondent = CaseParty::create([
            'case_id' => $case->id,
            'party_id' => $party->id,
            'role' => 'respondent',
        ]);
        $summons = Summons::factory()->create([
            'case_id' => $case->id,
            'respondent_case_party_id' => $respondent->id,
            'voided_at' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.cases.summons.void', [$case, $summons]), [
            'void_note' => 'Respondent moved to another city before service.',
        ]);

        $response->assertRedirect(route('admin.cases.show', $case));
        $summons->refresh();
        $this->assertNotNull($summons->voided_at);
        $this->assertSame('Respondent moved to another city before service.', $summons->void_note);
    }

    public function test_printable_summons_view_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $party = Party::factory()->create(['full_name' => 'Juan Respondent']);
        $respondent = CaseParty::create([
            'case_id' => $case->id,
            'party_id' => $party->id,
            'role' => 'respondent',
        ]);
        $summons = Summons::factory()->create([
            'case_id' => $case->id,
            'respondent_case_party_id' => $respondent->id,
            'summons_number' => 'SUM-2026-0042',
            'venue' => 'Barangay Mediation Hall',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.cases.summons.print', [$case, $summons]));

        $response->assertOk()
            ->assertSee('SUM-2026-0042')
            ->assertSee('Juan Respondent')
            ->assertSee('Barangay Mediation Hall');
    }
}
