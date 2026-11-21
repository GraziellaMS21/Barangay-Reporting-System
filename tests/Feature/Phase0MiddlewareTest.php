<?php

namespace Tests\Feature;

use App\Models\AgencyRegistration;
use App\Models\Office;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase0MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'setup.complete'])->get('/test-setup-target', fn () => 'target-page')->name('test.target');
        Route::middleware(['web'])->get('/setup', fn () => 'setup-page')->name('setup.show');
        Route::middleware(['web', 'auth', 'permission:cases.view'])->get('/test-permission', fn () => 'perm-ok');
        Route::middleware(['web', 'auth', 'password.changed'])->get('/test-password-gate', fn () => 'pw-ok');
        Route::middleware(['web'])->get('/account/password', fn () => 'pw-edit')->name('account.password.edit');
        Route::middleware(['web', 'auth', 'agency.active'])->get('/test-agency-gate', fn () => 'agency-ok');
        Route::middleware(['web'])->get('/agency/pending', fn () => 'agency-pending')->name('agency.pending');
        Route::middleware(['web', 'auth', 'role:staff'])->get('/test-role-staff', fn () => 'staff-ok');
        Route::middleware(['web', 'auth', 'role:agency'])->get('/test-role-agency', fn () => 'agency-ok');
    }

    public function test_setup_complete_middleware_redirects_when_incomplete(): void
    {
        $this->withoutSetup();

        $response = $this->get('/test-setup-target');
        $response->assertRedirect('/setup');

        // Excluded routes pass
        $this->get('/setup')->assertOk();
    }

    public function test_setup_complete_middleware_redirects_setup_when_complete(): void
    {
        $response = $this->get('/setup');
        $response->assertRedirect('/login');

        $this->get('/test-setup-target')->assertOk();
    }

    public function test_permission_middleware(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staffWithout = User::factory()->create(['role' => 'staff']);
        $staffWith = User::factory()->create(['role' => 'staff']);

        $perm = Permission::create(['slug' => 'cases.view', 'label' => 'View Cases', 'group' => 'cases']);
        $staffWith->permissions()->attach($perm);

        // Admin passes
        $this->actingAs($admin)->get('/test-permission')->assertOk();

        // Staff with permission passes
        $this->actingAs($staffWith)->get('/test-permission')->assertOk();

        // Staff without permission gets 403
        $this->actingAs($staffWithout)->get('/test-permission')->assertStatus(403);
    }

    public function test_password_changed_middleware(): void
    {
        $userMustChange = User::factory()->create(['must_change_password' => true]);
        $userNormal = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($userMustChange)->get('/test-password-gate')->assertRedirect('/account/password');
        $this->actingAs($userNormal)->get('/test-password-gate')->assertOk();
    }

    public function test_agency_active_middleware(): void
    {
        $office = Office::create(['name' => 'City Social Welfare', 'type' => 'welfare', 'is_internal' => false]);

        $agencyApproved = User::factory()->create([
            'role' => 'agency',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        AgencyRegistration::create([
            'user_id' => $agencyApproved->id,
            'office_id' => $office->id,
            'agency_name_claimed' => 'CSWDO',
            'position_title' => 'Officer',
            'contact_number' => '09171234567',
            'id_document_path' => 'doc.jpg',
            'status' => 'approved',
        ]);

        $agencyPending = User::factory()->create([
            'role' => 'agency',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        AgencyRegistration::create([
            'user_id' => $agencyPending->id,
            'office_id' => $office->id,
            'agency_name_claimed' => 'CSWDO',
            'position_title' => 'Officer',
            'contact_number' => '09171234567',
            'id_document_path' => 'doc.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($agencyApproved)->get('/test-agency-gate')->assertOk();
        $this->actingAs($agencyPending)->get('/test-agency-gate')->assertRedirect('/agency/pending');
    }

    public function test_ensure_role_middleware(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $agency = User::factory()->create(['role' => 'agency']);

        // role:staff admits staff and admin, rejects agency
        $this->actingAs($admin)->get('/test-role-staff')->assertOk();
        $this->actingAs($staff)->get('/test-role-staff')->assertOk();
        $this->actingAs($agency)->get('/test-role-staff')->assertStatus(403);

        // role:agency admits agency, rejects staff/admin
        $this->actingAs($agency)->get('/test-role-agency')->assertOk();
        $this->actingAs($staff)->get('/test-role-agency')->assertStatus(403);
    }
}
