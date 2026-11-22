<?php

namespace Tests\Feature\People;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\Category;
use App\Models\FiscalYear;
use App\Models\GeneratedReport;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsCenterTest extends TestCase
{
    use RefreshDatabase;

    private FiscalYear $fy;

    private CaseType $caseType;

    private Purok $purok;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            ['slug' => 'reports_center.view', 'label' => 'View Reports Center', 'group' => 'reports_center'],
            ['slug' => 'reports_center.export', 'label' => 'Export Reports', 'group' => 'reports_center'],
            ['slug' => 'cases.view', 'label' => 'View Cases', 'group' => 'cases'],
            ['slug' => 'cases.view_confidential', 'label' => 'View Confidential Cases', 'group' => 'cases'],
        ] as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        $this->fy = FiscalYear::firstOrFail();
        $this->purok = Purok::factory()->create(['name' => 'Purok 1']);
        $this->category = Category::create([
            'name' => 'Sanitation',
            'slug' => 'sanitation',
            'is_active' => true,
        ]);
        $this->caseType = CaseType::create([
            'name' => 'Physical Injury',
            'slug' => 'physical_injury',
            'is_active' => true,
        ]);
    }

    public function test_reports_center_catalog_and_all_eight_reports_render_and_respect_confidential_case_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $analyst = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $analyst->permissions()->sync(
            Permission::whereIn('slug', ['reports_center.view', 'reports_center.export', 'cases.view'])->pluck('id')
        );

        // 1 public case + 1 confidential case (encoded by admin)
        BarangayCase::create([
            'case_number' => '11-001-2026',
            'fiscal_year_id' => $this->fy->id,
            'case_type_id' => $this->caseType->id,
            'purok_id' => $this->purok->id,
            'title' => 'Public Dispute Case',
            'narrative' => 'Public case narrative text.',
            'filed_on' => '2026-11-10',
            'encoded_by' => $admin->id,
            'status' => CaseStatus::SETTLED,
            'is_confidential' => false,
        ]);

        BarangayCase::create([
            'case_number' => '11-002-2026',
            'fiscal_year_id' => $this->fy->id,
            'case_type_id' => $this->caseType->id,
            'purok_id' => $this->purok->id,
            'title' => 'Confidential VAWC Case',
            'narrative' => 'Confidential narrative text.',
            'filed_on' => '2026-11-12',
            'encoded_by' => $admin->id,
            'status' => CaseStatus::IN_MEDIATION,
            'is_confidential' => true,
        ]);

        Report::factory()->create([
            'fiscal_year_id' => $this->fy->id,
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => 'resolved',
        ]);

        // Analyst without cases.view_confidential opens catalog
        $this->actingAs($analyst)
            ->get(route('admin.reports-center.index'))
            ->assertOk()
            ->assertSee('KP Accomplishment Report (DILG)')
            ->assertSee('Monthly Case Summary by Type (DILG)')
            ->assertSee('Problems per Purok & Category Matrix');

        // Row-level case_summary excludes confidential case 11-002-2026 for analyst, but shows aggregate count = 2
        $this->actingAs($analyst)
            ->get(route('admin.reports-center.show', 'case_summary'))
            ->assertOk()
            ->assertSee('11-001-2026')
            ->assertDontSee('11-002-2026');

        // Admin sees both rows in case_summary
        $this->actingAs($admin)
            ->get(route('admin.reports-center.show', 'case_summary'))
            ->assertOk()
            ->assertSee('11-001-2026')
            ->assertSee('11-002-2026');

        // All 8 canonical reports render 200 OK
        foreach ([
            'kp_accomplishment',
            'case_summary',
            'purok_category_matrix',
            'problem_reports_summary',
            'referral_register',
            'summons_log',
            'personnel_list',
            'year_over_year',
        ] as $reportKey) {
            $this->actingAs($analyst)
                ->get(route('admin.reports-center.show', $reportKey))
                ->assertOk();
        }
    }

    public function test_reports_export_csv_xlsx_and_print_log_to_generated_reports_and_history(): void
    {
        $this->actingAsAdmin();

        // Print view
        $this->get(route('admin.reports-center.show', ['key' => 'kp_accomplishment', 'print' => 1]))
            ->assertOk()
            ->assertSee('KP Accomplishment Report (DILG)')
            ->assertSee('DRAFT');

        // CSV Export
        $this->post(route('admin.reports-center.export', 'kp_accomplishment'), [
            'format' => 'csv',
        ])->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // XLSX Export
        $this->post(route('admin.reports-center.export', 'kp_accomplishment'), [
            'format' => 'xlsx',
        ])->assertOk();

        $this->assertEquals(3, GeneratedReport::count());

        $latest = GeneratedReport::latest('id')->firstOrFail();

        // History page and re-download
        $this->get(route('admin.reports-center.history'))
            ->assertOk()
            ->assertSee('KP Accomplishment Report (DILG)');

        $this->get(route('admin.reports-center.download', $latest))
            ->assertOk();
    }
}
