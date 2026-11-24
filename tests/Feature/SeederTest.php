<?php

namespace Tests\Feature;

use App\Models\AgencyRegistration;
use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\EscalationDestination;
use App\Models\FiscalYear;
use App\Models\Permission;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\ReportTemplate;
use App\Models\Setting;
use App\Models\SignatoryAssignment;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_data_seeder_is_idempotent_and_seeds_unconfirmed_placeholders(): void
    {
        // First run
        $this->seed(RequiredDataSeeder::class);

        $permCount = Permission::count();
        $this->assertSame(21, $permCount);

        $posCount = Position::count();
        $this->assertSame(5, $posCount);
        $this->assertTrue(Position::where('is_confirmed', false)->exists());

        $caseTypeCount = CaseType::count();
        $this->assertSame(6, $caseTypeCount);

        $destCount = EscalationDestination::count();
        $this->assertSame(4, $destCount);

        $templateCount = ReportTemplate::count();
        $this->assertSame(3, $templateCount);

        $signatoryCount = SignatoryAssignment::count();
        $this->assertSame(8, $signatoryCount);

        // Run second time to verify idempotency
        $this->seed(RequiredDataSeeder::class);

        $this->assertSame($permCount, Permission::count());
        $this->assertSame($posCount, Position::count());
        $this->assertSame($caseTypeCount, CaseType::count());
        $this->assertSame($destCount, EscalationDestination::count());
        $this->assertSame($templateCount, ReportTemplate::count());
        $this->assertSame($signatoryCount, SignatoryAssignment::count());
    }

    public function test_demo_data_seeder_populates_active_fiscal_year_demo_users_and_cases(): void
    {
        $this->seed(DemoDataSeeder::class);

        // 1. Fiscal year
        $activeFy = FiscalYear::where('status', 'active')->first();
        $this->assertNotNull($activeFy);
        $this->assertSame((int) now()->year, $activeFy->year);

        // 2. Setup completed
        $setupSetting = Setting::where('key', 'system.setup_completed')->first();
        $this->assertNotNull($setupSetting);
        $this->assertTrue($setupSetting->is_confirmed);

        // 3. Demo accounts
        $admin = User::where('email', 'admin@calarian.gov.ph')->first();
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);

        $secretary = User::where('email', 'secretary@calarian.gov.ph')->first();
        $this->assertNotNull($secretary);
        $this->assertSame('admin', $secretary->role);

        $staff = User::where('email', 'staff@calarian.gov.ph')->first();
        $this->assertNotNull($staff);
        $this->assertSame('staff', $staff->role);
        $this->assertTrue($staff->permissions()->where('slug', 'reports_center.export')->exists());

        $encoder = User::where('email', 'clerk@calarian.gov.ph')->first();
        $this->assertNotNull($encoder);
        $this->assertSame('staff', $encoder->role);
        $this->assertTrue($encoder->permissions()->where('slug', 'cases.create')->exists());
        $this->assertFalse($encoder->permissions()->where('slug', 'settings.manage')->exists());

        $reviewer = User::where('email', 'reviewer@calarian.gov.ph')->first();
        $this->assertNotNull($reviewer);
        $this->assertSame('staff', $reviewer->role);
        $this->assertTrue($reviewer->permissions()->where('slug', 'cases.view_confidential')->exists());

        $agency = User::where('email', 'cswdo@zamboanga.gov.ph')->first();
        $this->assertNotNull($agency);
        $this->assertSame('agency', $agency->role);

        $this->assertSame(0, User::where('role', 'personnel')->count());

        $reg = AgencyRegistration::where('user_id', $agency->id)->first();
        $this->assertNotNull($reg);
        $this->assertSame('approved', $reg->status);
        $this->assertTrue($reg->is_office_admin);

        // 4. Personnel
        $this->assertTrue(Personnel::where('full_name', 'Hon. Eduardo V. Alvarez')->exists());
        $this->assertTrue(Personnel::where('full_name', 'Maria Clara Delos Santos')->exists());
        $this->assertTrue(Personnel::where('full_name', 'Rogelio B. Gutierrez')->exists());

        // 5. Demo cases (1 filed, 1 mediation, 1 settled, 1 escalated)
        $this->assertTrue(BarangayCase::where('status', 'filed')->exists());
        $this->assertTrue(BarangayCase::where('status', 'in_mediation')->exists());
        $this->assertTrue(BarangayCase::where('status', 'settled')->exists());
        $this->assertTrue(BarangayCase::where('status', 'escalated')->exists());
    }
}
