<?php

namespace Tests\Feature;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\Party;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteStubsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_on_protected_routes(): void
    {
        $this->get('/admin/cases')->assertRedirect('/login');
        $this->get('/admin/personnel')->assertRedirect('/login');
        $this->get('/admin/settings/general')->assertRedirect('/login');
        $this->get('/admin/reports-center')->assertRedirect('/login');
    }

    public function test_admin_can_access_all_route_stubs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $fy = FiscalYear::firstOrCreate(['year' => 2026], ['label' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']);
        $caseType = CaseType::create(['name' => 'General', 'slug' => 'general']);
        $case = BarangayCase::create([
            'case_number' => 'CASE-2026-11111',
            'fiscal_year_id' => $fy->id,
            'case_type_id' => $caseType->id,
            'title' => 'Test Case',
            'narrative' => 'Narrative text',
            'filed_on' => '2026-01-01',
            'encoded_by' => $admin->id,
            'status' => CaseStatus::FILED,
        ]);
        $party = Party::create([
            'full_name' => 'Juan dela Cruz',
            'residency_status' => 'resident',
        ]);

        $this->get('/admin/cases')->assertOk();
        $this->get('/admin/cases/create')->assertOk();
        $this->get("/admin/cases/{$case->id}")->assertOk();
        $this->get("/admin/cases/{$case->id}/edit")->assertOk();
        $this->get('/admin/parties')->assertOk();
        $this->get("/admin/parties/{$party->id}")->assertOk();
        $this->get('/admin/settings/general')->assertOk();
        $this->get('/admin/settings/fiscal-years')->assertOk();
        $this->get('/admin/settings/numbering')->assertOk();
        $this->get('/admin/settings/pending')->assertOk();
        $this->get('/admin/settings/templates')->assertOk();
        $this->get('/admin/settings/backups')->assertOk();
        $this->get('/admin/personnel')->assertOk();
        $this->get('/admin/positions')->assertOk();
        $this->get('/admin/signatories')->assertOk();
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/reports-center')->assertOk();
        $this->get('/admin/reports-center/history')->assertOk();
        $this->get('/admin/hearings')->assertOk();
        $this->get('/admin/secretary')->assertOk();
        $this->get('/admin/agencies')->assertOk();
    }

    public function test_staff_without_permissions_is_forbidden_on_admin_settings(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);

        $this->get('/admin/settings/general')->assertStatus(403);
        $this->get('/admin/settings/fiscal-years')->assertStatus(403);
        $this->get('/admin/settings/numbering')->assertStatus(403);
        $this->get('/admin/settings/backups')->assertStatus(403);
    }

    public function test_agency_user_cannot_access_staff_or_admin_routes(): void
    {
        $this->actingAsAgency();

        $this->get('/admin/cases')->assertStatus(403);
        $this->get('/admin/personnel')->assertStatus(403);
        $this->get('/admin/settings/general')->assertStatus(403);
        $this->get('/agency/referrals')->assertOk();
        $this->get('/agency/team')->assertOk();
    }
}
