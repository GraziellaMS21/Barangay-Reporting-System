<?php

namespace Tests\Feature\Admin;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\Party;
use App\Models\Report;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_global_search_returns_cross_year_results(): void
    {
        $fy2025 = FiscalYear::firstOrCreate(['year' => 2025], ['label' => 'FY 2025', 'starts_on' => '2025-01-01', 'ends_on' => '2025-12-31', 'status' => 'closed', 'is_locked' => true]);
        $fy2026 = FiscalYear::firstOrCreate(['year' => 2026], ['label' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active', 'is_locked' => false]);

        $report = Report::factory()->create([
            'fiscal_year_id' => $fy2025->id,
            'tracking_code' => 'REP-2025-9999',
            'description' => 'Uncollected waste accumulation along boundary.',
        ]);

        $caseType = CaseType::firstOrCreate(['slug' => 'general'], ['name' => 'General Dispute']);
        $case = BarangayCase::create([
            'case_number' => 'CASE-2026-8888',
            'fiscal_year_id' => $fy2026->id,
            'case_type_id' => $caseType->id,
            'title' => 'Boundary Dispute Between Cruz and Santos',
            'narrative' => 'Fence encroaches onto adjacent lot boundary.',
            'filed_on' => '2026-02-01',
            'status' => CaseStatus::FILED,
            'encoded_by' => auth()->id(),
            'is_confidential' => false,
        ]);

        $party = Party::create([
            'full_name' => 'Salvador M. Santos',
            'alias' => 'Badong',
            'contact_number' => '0917-888-7766',
        ]);

        // Search by tracking code
        $res1 = $this->get(route('admin.search', ['q' => 'REP-2025-9999']));
        $res1->assertStatus(200);
        $res1->assertSee('REP-2025-9999');

        // Search by party name
        $res2 = $this->get(route('admin.search', ['q' => 'Badong']));
        $res2->assertStatus(200);
        $res2->assertSee('Salvador M. Santos');

        // Search by case number
        $res3 = $this->get(route('admin.search', ['q' => 'CASE-2026-8888']));
        $res3->assertStatus(200);
        $res3->assertSee('Boundary Dispute Between Cruz and Santos');
    }
}
