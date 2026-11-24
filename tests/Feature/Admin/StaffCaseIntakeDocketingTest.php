<?php

namespace Tests\Feature\Admin;

use App\Models\CaseType;
use App\Models\ComplaintIntake;
use App\Models\FiscalYear;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffCaseIntakeDocketingTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        FiscalYear::firstOrCreate(['year' => 2026], [
            'label' => 'FY 2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'status' => 'active',
        ]);

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        // Give permissions
        foreach (['cases.view', 'cases.create', 'cases.edit'] as $slug) {
            $perm = Permission::firstOrCreate(['slug' => $slug], [
                'label' => $slug,
                'group' => 'cases',
            ]);
            $this->staff->permissions()->attach($perm);
        }

        Purok::create(['name' => 'Purok 1']);
        CaseType::firstOrCreate(['slug' => 'neighborhood-dispute'], [
            'name' => 'Neighborhood Dispute',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_navigation_displays_community_reports_and_cases_and_blotter(): void
    {
        $response = $this->actingAs($this->staff)->get(route('admin.cases.index'));

        $response->assertOk();
        $response->assertSee('Cases & Blotter');
        $response->assertSee('Community reports');
    }

    public function test_staff_can_view_pending_intakes_tab_on_cases_docket(): void
    {
        $intake = ComplaintIntake::create([
            'complainant_name' => 'Juan Dela Cruz',
            'complainant_contact' => '09171234567',
            'complainant_address' => 'Lower Calarian',
            'respondent_name' => 'Pedro Penduko',
            'respondent_address' => 'Upper Calarian',
            'what_happened' => 'Boundary altercation and loud music after midnight.',
            'who_involved' => 'Juan and Pedro',
            'when_happened' => now()->subDay(),
            'where_happened' => 'Lower Calarian Street',
            'why_how_happened' => 'Dispute over property fence and excessive noise.',
            'rights_violated' => 'Peace and quiet, boundary encroached.',
            'relief_requested' => 'Remove fence encroachment and observe quiet hours.',
            'case_type_suggestion' => 'Neighborhood Dispute',
            'confidentiality_screen_passed' => true,
            'consent_at' => now(),
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('admin.cases.index', ['tab' => 'pending_intake']));

        $response->assertOk();
        $response->assertSee('Pending Intakes');
        $response->assertSee('Juan Dela Cruz');
        $response->assertSee('Pedro Penduko');
        $response->assertSee('Docket this');
        $response->assertSee('Reject');
    }

    public function test_docket_this_prefills_case_creation_form_from_intake(): void
    {
        $intake = ComplaintIntake::create([
            'complainant_name' => 'Maria Makiling',
            'complainant_contact' => '09170001111',
            'complainant_address' => 'Purok 1, Calarian',
            'respondent_name' => 'Bernardo Carpio',
            'respondent_address' => 'Purok 1, Calarian',
            'what_happened' => 'Agricultural boundary encroachment along tree line.',
            'who_involved' => 'Maria and Bernardo',
            'when_happened' => now()->subDays(3),
            'where_happened' => 'Sitio Forestry boundary',
            'why_how_happened' => 'Cutting of shared bamboo fence.',
            'rights_violated' => 'Property ownership rights.',
            'relief_requested' => 'Replanting of fence and boundary apology.',
            'confidentiality_screen_passed' => true,
            'consent_at' => now(),
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('admin.cases.create', ['intake_id' => $intake->id]));

        $response->assertOk();
        $response->assertSee('Docketing from Resident Complaint Intake #'.$intake->id);
        $response->assertSee('Agricultural boundary encroachment along tree line.');
        $response->assertSee('Sitio Forestry boundary');
        $response->assertSee('Maria Makiling');
        $response->assertSee('Bernardo Carpio');
    }

    public function test_submitting_case_with_source_intake_id_marks_intake_docketed(): void
    {
        $intake = ComplaintIntake::create([
            'complainant_name' => 'Maria Makiling',
            'complainant_contact' => '09170001111',
            'complainant_address' => 'Purok 1, Calarian',
            'respondent_name' => 'Bernardo Carpio',
            'respondent_address' => 'Purok 1, Calarian',
            'what_happened' => 'Boundary encroachment.',
            'who_involved' => 'Maria and Bernardo',
            'when_happened' => now()->subDay(),
            'where_happened' => 'Calarian boundary',
            'why_how_happened' => 'Fence removal.',
            'rights_violated' => 'Property rights.',
            'relief_requested' => 'Repair fence.',
            'confidentiality_screen_passed' => true,
            'consent_at' => now(),
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $caseType = CaseType::first();
        $purok = Purok::first();

        $response = $this->actingAs($this->staff)->post(route('admin.cases.store'), [
            'case_type_id' => $caseType->id,
            'filed_on' => now()->toDateString(),
            'purok_id' => $purok->id,
            'narrative' => 'Detailed facts of the dispute regarding boundary encroachment.',
            'source' => 'complaint_intake',
            'source_intake_id' => $intake->id,
            'parties' => [
                [
                    'role' => 'complainant',
                    'full_name' => 'Maria Makiling',
                    'residency_status' => 'resident',
                    'contact_number' => '09170001111',
                ],
                [
                    'role' => 'respondent',
                    'full_name' => 'Bernardo Carpio',
                    'residency_status' => 'resident',
                    'contact_number' => '09172223333',
                ],
            ],
        ]);

        $response->assertRedirect();

        $intake->refresh();
        $this->assertEquals('docketed', $intake->status);
        $this->assertNotNull($intake->case_id);
        $this->assertEquals($this->staff->id, $intake->reviewed_by);
        $this->assertNotNull($intake->reviewed_at);
    }

    public function test_staff_can_reject_complaint_intake_with_mandatory_reason(): void
    {
        $intake = ComplaintIntake::create([
            'complainant_name' => 'Anonymous Complainant',
            'complainant_contact' => '09179998888',
            'complainant_address' => 'Lower Calarian',
            'respondent_name' => 'Unknown Respondent',
            'respondent_address' => 'Unknown',
            'what_happened' => 'Incomplete information submitted online without clear respondent details.',
            'who_involved' => 'Unknown',
            'when_happened' => now()->subDay(),
            'where_happened' => 'Unknown street',
            'why_how_happened' => 'Vague grievance.',
            'rights_violated' => 'None stated.',
            'relief_requested' => 'Advice.',
            'confidentiality_screen_passed' => true,
            'consent_at' => now(),
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->staff)->post(route('admin.cases.intakes.reject', $intake), [
            'outcome_reason' => 'insufficient_info',
            'explanation_note' => 'Respondent address and identity are unknown; cannot serve summons notices.',
        ]);

        $response->assertRedirect(route('admin.cases.index', ['tab' => 'pending_intake']));

        $intake->refresh();
        $this->assertEquals('rejected', $intake->status);
        $this->assertStringContainsString('insufficient_info', $intake->outcome_reason);
        $this->assertStringContainsString('Respondent address and identity are unknown', $intake->outcome_reason);
        $this->assertEquals($this->staff->id, $intake->reviewed_by);
        $this->assertNotNull($intake->reviewed_at);
    }
}
