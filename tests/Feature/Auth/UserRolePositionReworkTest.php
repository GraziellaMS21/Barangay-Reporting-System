<?php

namespace Tests\Feature\Auth;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\Category;
use App\Models\EmergencyContact;
use App\Models\FiscalYear;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Report;
use App\Models\ResponseTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Support\CaseStatus;
use App\Support\UserRole;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserRolePositionReworkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RequiredDataSeeder::class);
    }

    public function test_validation_matrix_enforces_role_specific_office_position_and_preset_rules(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $this->actingAs($admin);

        $externalOffice = Office::where('is_external', true)->firstOrFail();
        $internalOffice = Office::where('is_internal', true)->firstOrFail();
        $position = Position::firstOrFail();

        // 1. Agency user requires an external office
        $response = $this->post(route('admin.users.store'), [
            'name' => 'Agency Officer',
            'email' => 'agency.test@partner.gov.ph',
            'role' => UserRole::AGENCY,
        ]);
        $response->assertSessionHasErrors('office_id');

        // 2. Agency user cannot use an internal office
        $response = $this->post(route('admin.users.store'), [
            'name' => 'Agency Officer',
            'email' => 'agency.test@partner.gov.ph',
            'role' => UserRole::AGENCY,
            'office_id' => $internalOffice->id,
        ]);
        $response->assertSessionHasErrors('office_id');

        // 3. Agency user cannot have a position or preset
        $response = $this->post(route('admin.users.store'), [
            'name' => 'Agency Officer',
            'email' => 'agency.test@partner.gov.ph',
            'role' => UserRole::AGENCY,
            'office_id' => $externalOffice->id,
            'position_id' => $position->id,
            'preset' => 'staff_full',
        ]);
        $response->assertSessionHasErrors(['position_id', 'preset']);

        // 4. Staff requires a preset and cannot have an office
        $response = $this->post(route('admin.users.store'), [
            'name' => 'Staff Officer',
            'email' => 'staff.test@calarian.gov.ph',
            'role' => UserRole::STAFF,
            'office_id' => $externalOffice->id,
        ]);
        $response->assertSessionHasErrors(['preset', 'office_id']);

        // 5. Valid agency user creation succeeds
        $response = $this->post(route('admin.users.store'), [
            'name' => 'Valid Agency User',
            'email' => 'valid.agency@partner.gov.ph',
            'role' => UserRole::AGENCY,
            'office_id' => $externalOffice->id,
        ]);
        $response->assertRedirect(route('admin.users.index'));
        $createdAgency = User::where('email', 'valid.agency@partner.gov.ph')->firstOrFail();
        $this->assertSame(UserRole::AGENCY, $createdAgency->role);
        $this->assertSame($externalOffice->id, $createdAgency->office_id);

        // 6. Valid staff user creation with preset and position succeeds
        $response = $this->post(route('admin.users.store'), [
            'name' => 'Valid Staff User',
            'email' => 'valid.staff@calarian.gov.ph',
            'role' => UserRole::STAFF,
            'preset' => 'case_reviewer',
            'position_id' => $position->id,
        ]);
        $response->assertRedirect(route('admin.users.index'));
        $createdStaff = User::where('email', 'valid.staff@calarian.gov.ph')->firstOrFail();
        $this->assertSame(UserRole::STAFF, $createdStaff->role);
        $this->assertTrue($createdStaff->hasPermission('cases.view_confidential'));
        $this->assertSame($position->id, $createdStaff->personnel?->position_id);
    }

    public function test_personnel_role_migration_converts_legacy_users_to_staff(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Legacy Personnel User',
            'email' => 'legacy.personnel@calarian.gov.ph',
            'password' => 'secret',
            'role' => 'personnel',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_12_02_000001_migrate_personnel_role_to_staff.php');
        $migration->up();

        $user = User::findOrFail($userId);
        $this->assertSame(UserRole::STAFF, $user->role);
        $this->assertTrue($user->hasPermission('reports_center.view'));
    }

    public function test_required_data_seeder_run_twice_produces_no_duplicates(): void
    {
        $categoriesCount = Category::count();
        $officesCount = Office::count();
        $templatesCount = ResponseTemplate::count();
        $contactsCount = EmergencyContact::count();
        $permissionsCount = Permission::count();
        $settingsCount = Setting::count();
        $positionsCount = Position::count();

        // Run second time
        $this->seed(RequiredDataSeeder::class);

        $this->assertSame($categoriesCount, Category::count());
        $this->assertSame($officesCount, Office::count());
        $this->assertSame($templatesCount, ResponseTemplate::count());
        $this->assertSame($contactsCount, EmergencyContact::count());
        $this->assertSame($permissionsCount, Permission::count());
        $this->assertSame($settingsCount, Setting::count());
        $this->assertSame($positionsCount, Position::count());
    }

    public function test_re_running_required_data_seeder_does_not_clobber_edited_values(): void
    {
        // Edit values
        Setting::where('key', 'priority.urgency')->update(['value' => '999']);
        Office::firstOrFail()->update(['contact_phone' => '0999-999-9999']);
        Category::firstOrFail()->update(['sla_hours' => 123]);

        // Re-run
        $this->seed(RequiredDataSeeder::class);

        $this->assertSame('999', Setting::where('key', 'priority.urgency')->value('value'));
        $this->assertSame('0999-999-9999', Office::firstOrFail()->contact_phone);
        $this->assertSame(123, Category::firstOrFail()->sla_hours);
    }

    public function test_demo_data_seeder_run_twice_has_no_duplicates_and_no_personnel_role(): void
    {
        $this->seed(DemoDataSeeder::class);
        $userCount = User::count();
        $caseCount = BarangayCase::count();

        // Run second time
        $this->seed(DemoDataSeeder::class);

        $this->assertSame($userCount, User::count());
        $this->assertSame($caseCount, BarangayCase::count());
        $this->assertSame(0, User::where('role', 'personnel')->count());
    }

    public function test_secretary_has_admin_role_and_full_permissions(): void
    {
        $this->seed(DemoDataSeeder::class);

        $secretary = User::where('email', 'secretary@calarian.gov.ph')->firstOrFail();
        $this->assertSame(UserRole::ADMIN, $secretary->role);
        $this->assertTrue($secretary->isAdmin());
        $this->assertTrue($secretary->hasPermission('cases.view'));
        $this->assertTrue($secretary->hasPermission('settings.manage'));
    }

    public function test_database_seeder_creates_no_users_and_no_reports(): void
    {
        // Seed only DatabaseSeeder
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
        $this->assertSame(0, Report::count());
        $this->assertSame(0, BarangayCase::count());
    }

    public function test_role_based_access_and_confidential_case_isolation(): void
    {
        $this->seed(DemoDataSeeder::class);

        $agency = User::where('email', 'cswdo@zamboanga.gov.ph')->firstOrFail();
        $encoder = User::where('email', 'clerk@calarian.gov.ph')->firstOrFail();
        $reviewer = User::where('email', 'reviewer@calarian.gov.ph')->firstOrFail();
        $admin = User::where('email', 'admin@calarian.gov.ph')->firstOrFail();

        // 1. Agency isolation: cannot access internal reports or cases
        $this->actingAs($agency)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($agency)->get(route('admin.cases.index'))->assertForbidden();
        $this->actingAs($agency)->get(route('agency.referrals.index'))->assertOk();

        // 2. Create a confidential case encoded by admin
        $fy = FiscalYear::where('status', 'active')->firstOrFail();
        $caseType = CaseType::firstOrFail();
        $confidentialCase = BarangayCase::create([
            'case_number' => 'CASE-CONF-001',
            'fiscal_year_id' => $fy->id,
            'case_type_id' => $caseType->id,
            'record_type' => 'complaint',
            'source' => 'direct',
            'title' => 'Confidential Dispute Matter',
            'narrative' => 'Sensitive intra-family confidential altercation.',
            'filed_on' => now()->toDateString(),
            'status' => CaseStatus::FILED,
            'encoded_by' => $admin->id,
            'is_confidential' => true,
        ]);

        // Lupon encoder cannot view confidential case encoded by another user (isolated via 404)
        $this->actingAs($encoder)
            ->get(route('admin.cases.show', $confidentialCase))
            ->assertNotFound();

        // Case reviewer (Kagawad PO) CAN view confidential case
        $this->actingAs($reviewer)
            ->get(route('admin.cases.show', $confidentialCase))
            ->assertOk();
    }
}
