<?php

namespace Tests\Feature\Cases;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\Party;
use App\Models\User;
use App\Services\NumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseNumberingSchemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_case_numbering_format_is_month_sequence_year(): void
    {
        $fy = FiscalYear::firstOrCreate(
            ['year' => 2026],
            [
                'label' => 'FY 2026',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'status' => 'active',
            ]
        );

        $service = app(NumberingService::class);
        $caseNumber = $service->next('case', $fy);

        $currentMonth = now()->format('m');
        $expectedPrefix = "{$currentMonth}-001-2026";

        $this->assertEquals($expectedPrefix, $caseNumber);
    }

    public function test_case_creation_generates_standard_numbering(): void
    {
        $admin = User::factory()->admin()->create();

        $fy = FiscalYear::firstOrCreate(
            ['year' => 2026],
            [
                'label' => 'FY 2026',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'status' => 'active',
            ]
        );

        $caseType = CaseType::firstOrCreate(
            ['slug' => 'sample-dispute'],
            ['name' => 'Sample Dispute', 'sort_order' => 1]
        );

        $comp = Party::factory()->create();
        $resp = Party::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.cases.store'), [
            'case_type_id' => $caseType->id,
            'filed_on' => now()->toDateString(),
            'title' => 'Sample Dispute',
            'narrative' => 'This is a valid formal dispute narrative with sufficient details.',
            'parties' => [
                ['role' => 'complainant', 'party_id' => $comp->id],
                ['role' => 'respondent', 'party_id' => $resp->id],
            ],
        ]);

        $response->assertRedirect();

        $case = BarangayCase::latest()->first();
        $this->assertNotNull($case);

        $currentMonth = now()->format('m');
        $this->assertStringStartsWith("{$currentMonth}-", $case->case_number);
        $this->assertStringEndsWith('-2026', $case->case_number);
    }
}
