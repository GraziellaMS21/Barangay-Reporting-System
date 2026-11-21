<?php

namespace Tests\Unit;

use App\Models\FiscalYear;
use App\Models\NumberSequence;
use App\Services\NumberingService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbering_service_parses_format_and_increments_sequence(): void
    {
        $service = app(NumberingService::class);
        $settings = app(SettingsService::class);
        $settings->set('numbering.case.format', 'CASE-{YEAR}-{SEQ:5}');

        $fy = FiscalYear::where('year', 2026)->first() ?? FiscalYear::factory()->create(['year' => 2026, 'status' => 'active']);

        $code1 = $service->next('case', $fy);
        $this->assertSame('CASE-2026-00001', $code1);

        $code2 = $service->next('case', $fy);
        $this->assertSame('CASE-2026-00002', $code2);

        $seq = NumberSequence::where('type', 'case')
            ->where('fiscal_year_id', $fy->id)
            ->first();
        $this->assertNotNull($seq);
        $this->assertSame(2, $seq->last_number);
    }

    public function test_numbering_sequences_reset_across_fiscal_years(): void
    {
        $service = app(NumberingService::class);
        $settings = app(SettingsService::class);
        $settings->set('numbering.referral.format', 'REF-{YEAR}-{SEQ:4}');

        $fy2025 = FiscalYear::factory()->create(['year' => 2025, 'status' => 'closed']);
        $fy2026 = FiscalYear::where('year', 2026)->first() ?? FiscalYear::factory()->create(['year' => 2026, 'status' => 'active']);

        $code2025 = $service->next('referral', $fy2025);
        $this->assertSame('REF-2025-0001', $code2025);

        $code2026 = $service->next('referral', $fy2026);
        $this->assertSame('REF-2026-0001', $code2026);
    }
}
