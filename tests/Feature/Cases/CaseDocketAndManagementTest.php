<?php

namespace Tests\Feature\Cases;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\Category;
use App\Models\FiscalYear;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use App\Support\CaseStatus;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseDocketAndManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected FiscalYear $fy;

    protected CaseType $caseType;

    protected Purok $purok;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RequiredDataSeeder::class);

        $this->fy = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['label' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']
        );

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff = User::factory()->create(['role' => 'staff']);

        // Give staff cases permissions
        $permissions = Permission::whereIn('slug', ['cases.view', 'cases.create', 'cases.edit'])->get();
        $this->staff->permissions()->sync($permissions);

        $this->caseType = CaseType::first() ?? CaseType::create(['name' => 'Boundary Dispute', 'slug' => 'boundary-dispute']);
        $this->purok = Purok::first() ?? Purok::create(['name' => 'Purok 1']);
    }

    public function test_case_docket_lists_cases_with_active_fiscal_year_and_filters(): void
    {
        $this->actingAs($this->staff);

        $case = BarangayCase::create([
            'case_number' => 'CASE-2026-0001',
            'fiscal_year_id' => $this->fy->id,
            'case_type_id' => $this->caseType->id,
            'title' => 'Dela Cruz vs. Penduko',
            'narrative' => 'Dispute regarding lot boundaries and fence installation.',
            'filed_on' => '2026-02-10',
            'encoded_by' => $this->staff->id,
            'status' => CaseStatus::FILED,
            'purok_id' => $this->purok->id,
        ]);

        $response = $this->get('/admin/cases');
        $response->assertOk();
        $response->assertSee('CASE-2026-0001');
        $response->assertSee('Dela Cruz vs. Penduko');
    }

    public function test_case_csv_export_streams_filtered_cases(): void
    {
        $this->actingAs($this->staff);

        BarangayCase::create([
            'case_number' => 'CASE-2026-0002',
            'fiscal_year_id' => $this->fy->id,
            'case_type_id' => $this->caseType->id,
            'title' => 'Exportable Dispute',
            'narrative' => 'Detailed grievance narrative statement for testing.',
            'filed_on' => '2026-02-12',
            'encoded_by' => $this->staff->id,
            'status' => CaseStatus::IN_MEDIATION,
        ]);

        $response = $this->get('/admin/cases/export/csv');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('CASE-2026-0002', $response->streamedContent());
    }

    public function test_staff_can_docket_new_case_with_parties_and_snapshot_residency(): void
    {
        $this->actingAs($this->staff);

        $response = $this->post('/admin/cases', [
            'case_type_id' => $this->caseType->id,
            'filed_on' => now()->toDateString(),
            'incident_date' => now()->subDays(2)->toDateString(),
            'incident_location' => 'Purok 1 Basketball Court',
            'purok_id' => $this->purok->id,
            'title' => 'Custom Dispute Title',
            'narrative' => 'This is a valid long grievance narrative describing the neighborhood conflict in full detail.',
            'parties' => [
                [
                    'role' => 'complainant',
                    'full_name' => 'Juan Dela Cruz',
                    'residency_status' => 'resident',
                    'purok_id' => $this->purok->id,
                    'contact_number' => '0917-111-2222',
                ],
                [
                    'role' => 'respondent',
                    'full_name' => 'Pedro Penduko',
                    'residency_status' => 'non_resident',
                    'home_barangay' => 'Baliwasan',
                    'home_city' => 'Zamboanga City',
                    'contact_number' => '0918-333-4444',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cases', [
            'title' => 'Custom Dispute Title',
            'case_type_id' => $this->caseType->id,
            'status' => CaseStatus::FILED,
        ]);

        $createdCase = BarangayCase::where('title', 'Custom Dispute Title')->first();
        $this->assertNotNull($createdCase);
        $this->assertCount(2, $createdCase->caseParties);

        // Verify residency snapshot
        $this->assertDatabaseHas('case_parties', [
            'case_id' => $createdCase->id,
            'role' => 'complainant',
            'residency_at_filing' => 'resident',
        ]);
        $this->assertDatabaseHas('case_parties', [
            'case_id' => $createdCase->id,
            'role' => 'respondent',
            'residency_at_filing' => 'non_resident',
        ]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'case.created',
            'model' => BarangayCase::class,
            'model_id' => $createdCase->id,
        ]);
    }

    public function test_docketing_requires_at_least_one_complainant_and_one_respondent(): void
    {
        $this->actingAs($this->staff);

        $response = $this->post('/admin/cases', [
            'case_type_id' => $this->caseType->id,
            'filed_on' => now()->toDateString(),
            'narrative' => 'This is a valid grievance narrative that has sufficient length.',
            'parties' => [
                [
                    'role' => 'complainant',
                    'full_name' => 'Solo Complainant',
                    'residency_status' => 'resident',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['parties']);
    }

    public function test_docketing_with_action_issue_summons_redirects_to_summons_create(): void
    {
        $this->actingAs($this->staff);

        $response = $this->post('/admin/cases', [
            'action' => 'issue_summons',
            'case_type_id' => $this->caseType->id,
            'filed_on' => now()->toDateString(),
            'narrative' => 'This is a valid grievance narrative that has sufficient length.',
            'parties' => [
                [
                    'role' => 'complainant',
                    'full_name' => 'Maria Santos',
                    'residency_status' => 'resident',
                ],
                [
                    'role' => 'respondent',
                    'full_name' => 'Jose Rizal',
                    'residency_status' => 'resident',
                ],
            ],
        ]);

        $createdCase = BarangayCase::latest('id')->first();
        $this->assertNotNull($createdCase);
        $response->assertRedirect(route('admin.cases.summons.create', ['case' => $createdCase->id]));
    }

    public function test_convert_report_to_case_redirects_with_source_report_id(): void
    {
        $this->actingAs($this->staff);

        $category = Category::first() ?? Category::create(['name' => 'Disturbance', 'slug' => 'disturbance']);

        $report = Report::create([
            'tracking_code' => 'BRG-123456',
            'description' => 'Neighbor playing loud karaoke at 2 AM repeatedly.',
            'category_id' => $category->id,
            'status' => 'under_review',
            'fiscal_year_id' => $this->fy->id,
        ]);

        $response = $this->post("/admin/reports/{$report->id}/convert-to-case");
        $response->assertRedirect(route('admin.cases.create', ['source_report_id' => $report->id]));
    }

    public function test_case_show_renders_workspace_tabs_and_logs_view_audit(): void
    {
        $this->actingAs($this->staff);

        $case = BarangayCase::create([
            'case_number' => 'CASE-2026-9999',
            'fiscal_year_id' => $this->fy->id,
            'case_type_id' => $this->caseType->id,
            'title' => 'Workspace Test Case',
            'narrative' => 'Grievance facts statement for checking workspace tab views.',
            'filed_on' => '2026-03-01',
            'encoded_by' => $this->staff->id,
            'status' => CaseStatus::FILED,
        ]);

        $response = $this->get("/admin/cases/{$case->id}");
        $response->assertOk();
        $response->assertSee('CASE-2026-9999');
        $response->assertSee('Workspace Test Case');
        $response->assertSee('Overview');
        $response->assertSee('Parties');
        $response->assertSee('Summons');

        // Audit view logged
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'case.viewed',
            'model' => BarangayCase::class,
            'model_id' => $case->id,
        ]);
    }

    public function test_case_status_transition_updates_status_and_logs(): void
    {
        $this->actingAs($this->staff);

        $case = BarangayCase::create([
            'case_number' => 'CASE-2026-7777',
            'fiscal_year_id' => $this->fy->id,
            'case_type_id' => $this->caseType->id,
            'title' => 'Transition Test Case',
            'narrative' => 'Valid complaint statement to test status machine transition.',
            'filed_on' => '2026-03-01',
            'encoded_by' => $this->staff->id,
            'status' => CaseStatus::FILED,
        ]);

        $response = $this->post("/admin/cases/{$case->id}/status", [
            'to_status' => CaseStatus::IN_MEDIATION,
            'note' => 'Parties convened for preliminary mediation conciliation session.',
        ]);

        $response->assertRedirect(route('admin.cases.show', $case));
        $this->assertSame(CaseStatus::IN_MEDIATION, $case->fresh()->status);

        $this->assertDatabaseHas('case_status_logs', [
            'case_id' => $case->id,
            'from_status' => CaseStatus::FILED,
            'to_status' => CaseStatus::IN_MEDIATION,
            'note' => 'Parties convened for preliminary mediation conciliation session.',
        ]);
    }

    public function test_case_staff_assignment_and_notes(): void
    {
        $this->actingAs($this->staff);

        $case = BarangayCase::create([
            'case_number' => 'CASE-2026-6666',
            'fiscal_year_id' => $this->fy->id,
            'case_type_id' => $this->caseType->id,
            'title' => 'Assign Test Case',
            'narrative' => 'Valid complaint statement for checking assign and note functions.',
            'filed_on' => '2026-03-01',
            'encoded_by' => $this->staff->id,
            'status' => CaseStatus::FILED,
        ]);

        // Assign staff
        $assignResponse = $this->post("/admin/cases/{$case->id}/assign", [
            'assigned_to' => $this->staff->id,
        ]);
        $assignResponse->assertRedirect();
        $this->assertEquals($this->staff->id, $case->fresh()->assigned_to);

        // Add internal note
        $noteResponse = $this->post("/admin/cases/{$case->id}/notes", [
            'body' => 'Investigator visited the premises to inspect damaged property.',
        ]);
        $noteResponse->assertRedirect();
        $this->assertDatabaseHas('case_notes', [
            'case_id' => $case->id,
            'user_id' => $this->staff->id,
            'body' => 'Investigator visited the premises to inspect damaged property.',
        ]);
    }

    public function test_case_print_summary_renders(): void
    {
        $this->actingAs($this->staff);

        $case = BarangayCase::create([
            'case_number' => 'CASE-2026-PRINT',
            'fiscal_year_id' => $this->fy->id,
            'case_type_id' => $this->caseType->id,
            'title' => 'Print Summary Test Case',
            'narrative' => 'Case narrative facts statement for checking print rendering.',
            'filed_on' => '2026-03-01',
            'encoded_by' => $this->staff->id,
            'status' => CaseStatus::FILED,
        ]);

        $response = $this->get("/admin/cases/{$case->id}/print");
        $response->assertOk();
        $response->assertSee('CASE-2026-PRINT');
        $response->assertSee('BLOTTER DOCKET RECORD');
    }

    public function test_confidential_case_is_hidden_from_unauthorized_staff(): void
    {
        $unauthorizedStaff = User::factory()->create(['role' => 'staff']);
        $unauthorizedStaff->permissions()->sync(
            Permission::where('slug', 'cases.view')->get()
        );

        $confidentialCase = BarangayCase::create([
            'case_number' => 'CASE-2026-SECRET',
            'fiscal_year_id' => $this->fy->id,
            'case_type_id' => $this->caseType->id,
            'title' => 'Secret Family Dispute',
            'narrative' => 'Confidential domestic matter protected by RA 10173 privacy regulations.',
            'filed_on' => '2026-03-01',
            'encoded_by' => $this->admin->id,
            'status' => CaseStatus::FILED,
            'is_confidential' => true,
        ]);

        // Unauthorized staff sees 404 (invisible per SPEC_ADDENDUM.md section 7)
        $this->actingAs($unauthorizedStaff);
        $response = $this->get("/admin/cases/{$confidentialCase->id}");
        $response->assertNotFound();

        // Admin can access
        $this->actingAs($this->admin);
        $this->get("/admin/cases/{$confidentialCase->id}")->assertOk();
    }
}
