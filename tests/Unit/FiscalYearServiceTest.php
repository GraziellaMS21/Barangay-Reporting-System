<?php

namespace Tests\Unit;

use App\Exceptions\FiscalYearLocked;
use App\Models\FiscalYear;
use App\Models\Report;
use App\Services\FiscalYearContext;
use App\Services\FiscalYearGuard;
use App\Services\FiscalYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalYearServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_year_retrieval_and_switching(): void
    {
        $service = app(FiscalYearService::class);
        $fy2025 = FiscalYear::factory()->create(['year' => 2025, 'status' => 'closed']);
        $fy2026 = FiscalYear::where('year', 2026)->first();

        $active = $service->active();
        $this->assertSame(2026, $active->year);

        $service->activate($fy2025);
        $this->assertSame('active', $fy2025->fresh()->status);
        $this->assertSame('inactive', $fy2026->fresh()->status);
    }

    public function test_fiscal_year_context_binds_session_override(): void
    {
        $context = app(FiscalYearContext::class);
        $fy2025 = FiscalYear::factory()->create(['year' => 2025, 'status' => 'closed']);

        $this->assertSame(2026, $context->viewing()->year);

        $context->setViewing($fy2025->id);
        $this->assertSame(2025, $context->viewing()->year);
    }

    public function test_fiscal_year_guard_prevents_modifications_on_locked_years(): void
    {
        $guard = app(FiscalYearGuard::class);
        $lockedFy = FiscalYear::factory()->create(['year' => 2024, 'status' => 'locked', 'is_locked' => true]);

        $report = Report::factory()->create([
            'fiscal_year_id' => $lockedFy->id,
            'status' => 'resolved',
        ]);

        $this->expectException(FiscalYearLocked::class);
        $guard->assertWritable($report);
    }
}
