<?php

namespace Tests\Unit;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Report;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase0ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_confirmation_trait_works(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $position = Position::create([
            'name' => 'Lupon Member',
            'code' => 'lupon_member',
            'is_confirmed' => false,
        ]);

        $this->assertCount(1, Position::unconfirmed()->get());

        $position->markConfirmed($admin);
        $this->assertTrue($position->fresh()->is_confirmed);
        $this->assertCount(0, Position::unconfirmed()->get());
    }

    public function test_belongs_to_fiscal_year_trait_works(): void
    {
        $fy2025 = FiscalYear::firstOrCreate(
            ['year' => 2025],
            [
                'label' => 'FY 2025',
                'starts_on' => '2025-01-01',
                'ends_on' => '2025-12-31',
                'status' => 'inactive',
            ]
        );
        $fy2026 = FiscalYear::firstOrCreate(
            ['year' => 2026],
            [
                'label' => 'FY 2026',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'status' => 'active',
            ]
        );

        $report2025 = Report::factory()->create([
            'fiscal_year_id' => $fy2025->id,
            'created_at' => '2025-06-01 12:00:00',
        ]);
        $report2026 = Report::factory()->create([
            'fiscal_year_id' => $fy2026->id,
            'created_at' => '2026-06-01 12:00:00',
        ]);

        $this->assertEquals($fy2025->id, $report2025->fiscalYear->id);
        $this->assertCount(1, Report::forFiscalYear($fy2025)->get());
        $this->assertCount(1, Report::forFiscalYear($fy2026)->get());
        $this->assertCount(2, Report::forFiscalYear(null)->get());
    }

    public function test_user_roles_and_permissions_work(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $agency = User::factory()->create(['role' => 'agency']);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isStaff());
        $this->assertFalse($admin->isAgency());

        $this->assertFalse($staff->isAdmin());
        $this->assertTrue($staff->isStaff());
        $this->assertFalse($staff->isAgency());

        $this->assertFalse($agency->isAdmin());
        $this->assertFalse($agency->isStaff());
        $this->assertTrue($agency->isAgency());

        // Admin has all permissions automatically
        $this->assertTrue($admin->hasPermission('cases.view'));
        $this->assertTrue($admin->hasPermission('any.random.slug'));

        // Staff without permission
        $this->assertFalse($staff->hasPermission('cases.view'));

        // Attach permission to staff
        $perm = Permission::create([
            'slug' => 'cases.view',
            'label' => 'View Cases',
            'group' => 'cases',
        ]);
        $staff->permissions()->attach($perm);

        $this->assertTrue($staff->fresh()->hasPermission('cases.view'));
    }

    public function test_barangay_case_confidentiality_scope(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staffEncoder = User::factory()->create(['role' => 'staff']);
        $staffAssignee = User::factory()->create(['role' => 'staff']);
        $staffOther = User::factory()->create(['role' => 'staff']);
        $staffPrivileged = User::factory()->create(['role' => 'staff']);

        $permConfidential = Permission::create([
            'slug' => 'cases.view_confidential',
            'label' => 'View Confidential Cases',
            'group' => 'cases',
        ]);
        $staffPrivileged->permissions()->attach($permConfidential);

        $fy = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['label' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']
        );
        $caseType = CaseType::create(['name' => 'General', 'slug' => 'general']);

        $publicCase = BarangayCase::create([
            'case_number' => 'CASE-2026-00001',
            'fiscal_year_id' => $fy->id,
            'case_type_id' => $caseType->id,
            'title' => 'Public Dispute',
            'narrative' => 'A standard public dispute narrative.',
            'filed_on' => '2026-01-10',
            'encoded_by' => $staffEncoder->id,
            'status' => CaseStatus::FILED,
            'is_confidential' => false,
        ]);

        $confidentialCase = BarangayCase::create([
            'case_number' => 'CASE-2026-00002',
            'fiscal_year_id' => $fy->id,
            'case_type_id' => $caseType->id,
            'title' => 'Confidential Dispute',
            'narrative' => 'A confidential dispute narrative.',
            'filed_on' => '2026-01-11',
            'encoded_by' => $staffEncoder->id,
            'assigned_to' => $staffAssignee->id,
            'status' => CaseStatus::FILED,
            'is_confidential' => true,
        ]);

        // Admin sees both
        $this->assertCount(2, BarangayCase::visibleTo($admin)->get());

        // Encoder sees both
        $this->assertCount(2, BarangayCase::visibleTo($staffEncoder)->get());

        // Assignee sees both
        $this->assertCount(2, BarangayCase::visibleTo($staffAssignee)->get());

        // Privileged staff with cases.view_confidential sees both
        $this->assertCount(2, BarangayCase::visibleTo($staffPrivileged)->get());

        // Other staff only sees public case
        $visibleToOther = BarangayCase::visibleTo($staffOther)->get();
        $this->assertCount(1, $visibleToOther);
        $this->assertEquals($publicCase->id, $visibleToOther->first()->id);
    }
}
