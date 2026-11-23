<?php

namespace Tests\Feature\CaseActions;

use App\Models\BarangayCase;
use App\Models\CaseParty;
use App\Models\Summons;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ws4PlaceholdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_workspace_labels_incomplete_ws4_features_as_unavailable(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.cases.show', $case))
            ->assertOk()
            ->assertSee('Service, attendance, voiding, and printed summons')
            ->assertSee('Hearing schedule and outcomes')
            ->assertSee('Settlement record')
            ->assertSee('Deadline tracking')
            ->assertSee('Escalation, CFA, endorsement, and referral handoff');
    }

    public function test_summons_service_placeholder_does_not_change_the_summons_record(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $respondent = CaseParty::factory()->for($case, 'case')->create([
            'role' => 'respondent',
        ]);
        $summons = Summons::factory()->create([
            'case_id' => $case->id,
            'respondent_case_party_id' => $respondent->id,
            'service_result' => 'pending',
            'served_on' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.cases.summons.service', [$case, $summons]))
            ->assertOk()
            ->assertSee('Recording summons service details is not available yet.')
            ->assertSee('No information has been saved, changed, or sent from this screen.');

        $this->assertDatabaseHas('summons', [
            'id' => $summons->id,
            'service_result' => 'pending',
            'served_on' => null,
        ]);
    }

    public function test_hearing_placeholder_does_not_create_a_hearing(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.cases.hearings.store', $case))
            ->assertOk()
            ->assertSee('Scheduling a hearing is not available yet.');

        $this->assertDatabaseCount('hearings', 0);
    }

    public function test_settlement_placeholder_does_not_change_the_case(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
            'settled_on' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.cases.settlement.store', $case))
            ->assertOk()
            ->assertSee('Recording settlement terms or attaching an agreement is not available yet.');

        $this->assertDatabaseHas('cases', [
            'id' => $case->id,
            'status' => CaseStatus::IN_MEDIATION,
            'settled_on' => null,
        ]);
    }

    public function test_escalation_placeholder_does_not_escalate_or_create_a_referral(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.cases.escalations.store', $case))
            ->assertOk()
            ->assertSee('The escalation workflow is not available yet.')
            ->assertSee('No case status changed and no referral was sent.');

        $this->assertDatabaseHas('cases', [
            'id' => $case->id,
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $this->assertDatabaseCount('case_escalations', 0);
    }

    public function test_secretary_dashboard_route_displays_a_placeholder(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.secretary'))
            ->assertOk()
            ->assertSee('The dashboard is not available yet.')
            ->assertSee('Hearing, summons-service, and deadline reminders are not being calculated.');
    }

    public function test_escalation_destination_management_route_displays_a_placeholder(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.escalation-destinations.index'))
            ->assertOk()
            ->assertSee('Viewing and managing escalation destinations is not available yet.');
    }
}
