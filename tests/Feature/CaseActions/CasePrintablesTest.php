<?php

namespace Tests\Feature\CaseActions;

use App\Models\BarangayCase;
use App\Models\CaseEscalation;
use App\Models\CaseParty;
use App\Models\EscalationDestination;
use App\Models\Hearing;
use App\Models\Party;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\SignatoryAssignment;
use App\Models\Summons;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CasePrintablesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected BarangayCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $this->case = BarangayCase::factory()->create([
            'case_number' => 'BC-2026-0001',
            'title' => 'Boundary Line Dispute',
            'narrative' => 'Complainant alleges respondent built a fence beyond the boundary marker.',
            'status' => CaseStatus::IN_MEDIATION,
            'settled_on' => now()->toDateString(),
            'settlement_terms' => 'Respondent agreed to adjust the perimeter fence by 0.5 meters.',
            'settlement_amount' => 1500.00,
        ]);

        $compParty = Party::factory()->create(['full_name' => 'Maria Santos', 'address_line' => 'Zone 1, Calarian']);
        $respParty = Party::factory()->create(['full_name' => 'Juan Dela Cruz', 'address_line' => 'Zone 2, Calarian']);

        CaseParty::create([
            'case_id' => $this->case->id,
            'party_id' => $compParty->id,
            'role' => 'complainant',
            'residency_at_filing' => 'resident',
        ]);

        CaseParty::create([
            'case_id' => $this->case->id,
            'party_id' => $respParty->id,
            'role' => 'respondent',
            'residency_at_filing' => 'resident',
        ]);
    }

    public function test_certified_blotter_extract_renders_cleanly(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.cases.printables.blotter-extract', $this->case));

        $response->assertOk()
            ->assertSeeText('CERTIFIED BLOTTER EXTRACT')
            ->assertSeeText('BC-2026-0001')
            ->assertSeeText('Maria Santos')
            ->assertSeeText('Juan Dela Cruz')
            ->assertSeeText('Draft - Pending Barangay Confirmation')
            ->assertDontSeeText('Not provided')
            ->assertDontSeeText('N/A');
    }

    public function test_formal_complaint_renders_cleanly(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.cases.printables.complaint', $this->case));

        $response->assertOk()
            ->assertSeeText('COMPLAINT')
            ->assertSeeText('KP Form No. 1')
            ->assertSeeText('BC-2026-0001')
            ->assertSeeText('Maria Santos')
            ->assertSeeText('Juan Dela Cruz')
            ->assertDontSeeText('Not provided');
    }

    public function test_summons_notice_renders_cleanly(): void
    {
        $respCaseParty = $this->case->caseParties()->where('role', 'respondent')->first();
        $summons = Summons::create([
            'case_id' => $this->case->id,
            'respondent_case_party_id' => $respCaseParty->id,
            'sequence_no' => 1,
            'summons_number' => 'SUM-2026-0001',
            'issued_by' => $this->admin->id,
            'issued_on' => now()->toDateString(),
            'hearing_at' => now()->addDays(3),
            'venue' => 'Barangay Hall Mediation Room',
            'status' => 'issued',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cases.printables.summons', [$this->case, $summons]));

        $response->assertOk()
            ->assertSeeText('SUMMONS')
            ->assertSeeText('KP Form No. 7')
            ->assertSeeText('SUM-2026-0001')
            ->assertSeeText('Juan Dela Cruz')
            ->assertSeeText('OFFICER\'S RETURN OF SERVICE')
            ->assertDontSeeText('Not provided');
    }

    public function test_notice_of_hearing_renders_cleanly(): void
    {
        $hearing = Hearing::factory()->create([
            'case_id' => $this->case->id,
            'scheduled_at' => now()->addDays(2),
            'venue' => 'Barangay Hall Hearing Room',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cases.printables.conciliation-notice', [$this->case, $hearing]));

        $response->assertOk()
            ->assertSeeText('NOTICE OF HEARING')
            ->assertSeeText('KP Form No. 8')
            ->assertSeeText('BC-2026-0001')
            ->assertSeeText('Maria Santos')
            ->assertSeeText('Juan Dela Cruz')
            ->assertDontSeeText('Not provided');
    }

    public function test_amicable_settlement_agreement_renders_cleanly(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.cases.printables.settlement', $this->case));

        $response->assertOk()
            ->assertSeeText('AMICABLE SETTLEMENT')
            ->assertSeeText('KP Form No. 16')
            ->assertSeeText('BC-2026-0001')
            ->assertSeeText('Respondent agreed to adjust the perimeter fence by 0.5 meters.')
            ->assertSeeText('PHP 1,500.00')
            ->assertDontSeeText('Not provided');
    }

    public function test_certificate_to_file_action_renders_cleanly_with_signatories(): void
    {
        $destination = EscalationDestination::create([
            'name' => 'Municipal Trial Court in Cities (MTCC)',
            'type' => 'court',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $compCaseParty = $this->case->caseParties()->where('role', 'complainant')->first();

        $escalation = CaseEscalation::create([
            'case_id' => $this->case->id,
            'requested_by_case_party_id' => $compCaseParty->id,
            'requested_on' => now()->toDateString(),
            'escalation_destination_id' => $destination->id,
            'escalated_by' => $this->admin->id,
            'escalated_at' => now(),
            'cfa_number' => 'CFA-2026-0001',
            'reason' => 'Parties reached irreconcilable impasse after multiple hearings.',
        ]);

        $pos = Position::firstOrCreate(['code' => 'pangkat_chairman'], ['name' => 'Pangkat Chairman', 'sort_order' => 1]);
        $personnel = Personnel::create([
            'position_id' => $pos->id,
            'full_name' => 'Hon. Alberto Mendoza',
            'is_active' => true,
            'term_start' => now()->subYear()->toDateString(),
            'appointed_on' => now()->subYear()->toDateString(),
        ]);

        SignatoryAssignment::create([
            'document_type' => 'certificate_to_file_action',
            'role_label' => 'Approved by',
            'position_id' => $pos->id,
            'sort_order' => 1,
            'is_confirmed' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cases.printables.cfa', $this->case));

        $response->assertOk()
            ->assertSeeText('CERTIFICATION TO FILE ACTION (CFA)')
            ->assertSeeText('KP Form No. 20')
            ->assertSeeText('CFA-2026-0001')
            ->assertSeeText('Municipal Trial Court in Cities (MTCC)')
            ->assertSeeText('Hon. Alberto Mendoza')
            ->assertDontSeeText('Draft - Pending Barangay Confirmation') // Because is_confirmed = true
            ->assertDontSeeText('Not provided');
    }
}
