<?php

namespace Tests\Unit;

use App\Exceptions\FiscalYearLocked;
use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\Permission;
use App\Models\Report;
use App\Models\User;
use App\Services\CaseStatusService;
use App\Services\FiscalYearGuard;
use App\Services\FiscalYearService;
use App\Services\GlobalSearchService;
use App\Services\NumberingService;
use App\Services\OverdueService;
use App\Services\PermissionService;
use App\Services\ReferralService;
use App\Services\SettingsService;
use App\Services\SignatoryResolver;
use App\Services\SummonsCounterService;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase0ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_service_crud_and_helper(): void
    {
        $settings = app(SettingsService::class);
        $admin = User::factory()->create(['role' => 'admin']);

        // Default value
        $this->assertEquals('default_val', $settings->get('custom.key', 'default_val'));
        $this->assertEquals('default_val', setting('custom.key', 'default_val'));

        // Set
        $settings->set('custom.key', 'hello_world', $admin);
        $this->assertEquals('hello_world', $settings->get('custom.key'));
        $this->assertEquals('hello_world', setting('custom.key'));

        // Bool and int
        $settings->set('flag.enabled', true, $admin);
        $this->assertTrue($settings->getBool('flag.enabled'));

        $settings->set('limit.count', 42, $admin);
        $this->assertEquals(42, $settings->getInt('limit.count'));

        // Confirm
        $this->assertFalse($settings->isConfirmed('custom.key'));
        $settings->confirm('custom.key', $admin);
        $this->assertTrue($settings->isConfirmed('custom.key'));
    }

    public function test_fiscal_year_service_lifecycle(): void
    {
        $fyService = app(FiscalYearService::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $fy2030 = $fyService->create(2030);
        $this->assertEquals(2030, $fy2030->year);
        $this->assertEquals('inactive', $fy2030->status);

        $fyService->activate($fy2030);
        $this->assertEquals(2030, $fyService->active()->year);

        $fyService->close($fy2030, $admin, 'Closing year 2030.');
        $this->assertTrue($fy2030->fresh()->is_locked);
        $this->assertEquals('closed', $fy2030->fresh()->status);

        $fyService->reopen($fy2030, $admin, 'Reopening year 2030.');
        $this->assertFalse($fy2030->fresh()->is_locked);
    }

    public function test_numbering_service(): void
    {
        $numbering = app(NumberingService::class);
        $fyService = app(FiscalYearService::class);

        $fy = $fyService->create(2028);
        $fyService->activate($fy);

        $preview1 = $numbering->preview('referral', $fy);
        $this->assertEquals('REF-2028-0001', $preview1);

        $next1 = $numbering->next('referral', $fy);
        $this->assertEquals('REF-2028-0001', $next1);

        $next2 = $numbering->next('referral', $fy);
        $this->assertEquals('REF-2028-0002', $next2);

        $case1 = $numbering->next('case', $fy);
        $this->assertEquals('CASE-2028-00001', $case1);
    }

    public function test_fiscal_year_guard(): void
    {
        $guard = app(FiscalYearGuard::class);
        $fy = FiscalYear::create([
            'year' => 2024,
            'label' => 'FY 2024',
            'starts_on' => '2024-01-01',
            'ends_on' => '2024-12-31',
            'status' => 'closed',
            'is_locked' => true,
        ]);

        $report = Report::factory()->create([
            'fiscal_year_id' => $fy->id,
            'status' => 'resolved',
        ]);

        $this->expectException(FiscalYearLocked::class);
        $guard->assertWritable($report);
    }

    public function test_permission_service(): void
    {
        $permService = app(PermissionService::class);
        $user = User::factory()->create(['role' => 'staff']);

        Permission::create(['slug' => 'cases.view', 'label' => 'View Cases', 'group' => 'cases']);
        Permission::create(['slug' => 'cases.create', 'label' => 'Create Cases', 'group' => 'cases']);

        $permService->sync($user, ['cases.view']);
        $this->assertTrue($user->hasPermission('cases.view'));
        $this->assertFalse($user->hasPermission('cases.create'));

        $permService->applyPreset($user, 'case_encoder');
        $this->assertTrue($user->fresh()->hasPermission('cases.create'));
    }

    public function test_stubs_are_callable(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $resolver = app(SignatoryResolver::class);
        $this->assertSame([], $resolver->forDocument('summons'));

        $search = app(GlobalSearchService::class);
        $results = $search->search('test', $user);
        $this->assertArrayHasKey('cases', $results);

        $overdue = app(OverdueService::class);
        $fy = FiscalYear::firstOrCreate(['year' => 2026], ['label' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']);
        $caseType = CaseType::create(['name' => 'General', 'slug' => 'general']);
        $case = BarangayCase::create([
            'case_number' => 'CASE-2026-99999',
            'fiscal_year_id' => $fy->id,
            'case_type_id' => $caseType->id,
            'title' => 'Test Case',
            'narrative' => 'Test case narrative text.',
            'filed_on' => '2026-01-01',
            'encoded_by' => $user->id,
            'status' => CaseStatus::FILED,
        ]);

        $res = $overdue->forCase($case);
        $this->assertIsBool($res['is_overdue']);

        $counter = app(SummonsCounterService::class);
        $count = $counter->forCase($case)['issued'];
        $this->assertSame(0, $count);

        $caseStatusService = app(CaseStatusService::class);
        $this->assertTrue($caseStatusService->canTransition(CaseStatus::FILED, CaseStatus::IN_MEDIATION));
        $this->assertFalse($caseStatusService->canTransition(CaseStatus::FILED, CaseStatus::ESCALATED));

        $transitioned = $caseStatusService->transition($case, CaseStatus::IN_MEDIATION, 'Mediation started', $user);
        $this->assertEquals(CaseStatus::IN_MEDIATION, $transitioned->status);

        $referralService = app(ReferralService::class);
        $this->assertCount(0, $referralService->dueForFollowUp());
    }
}
