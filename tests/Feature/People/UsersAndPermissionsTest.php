<?php

namespace Tests\Feature\People;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsersAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            ['slug' => 'users.manage', 'label' => 'Manage Users', 'group' => 'users'],
            ['slug' => 'cases.view', 'label' => 'View Cases', 'group' => 'cases'],
            ['slug' => 'cases.view_confidential', 'label' => 'View Confidential Cases', 'group' => 'cases'],
            ['slug' => 'cases.create', 'label' => 'Create Cases', 'group' => 'cases'],
            ['slug' => 'cases.edit', 'label' => 'Edit Cases', 'group' => 'cases'],
            ['slug' => 'cases.summons', 'label' => 'Issue Summons', 'group' => 'cases'],
            ['slug' => 'reports_center.view', 'label' => 'View Reports Center', 'group' => 'reports_center'],
            ['slug' => 'reports_center.export', 'label' => 'Export Reports', 'group' => 'reports_center'],
            ['slug' => 'dashboard.view', 'label' => 'View Dashboard', 'group' => 'dashboard'],
        ] as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }
    }

    public function test_user_creation_presets_granular_permissions_and_two_admin_guard(): void
    {
        $captain = User::factory()->create(['name' => 'Punong Barangay', 'role' => 'admin', 'is_active' => true]);
        $this->actingAs($captain);

        // Create second admin (Barangay Secretary) per Q6
        $this->post(route('admin.users.store'), [
            'name' => 'Barangay Secretary',
            'email' => 'secretary@calarian.gov.ph',
            'role' => 'admin',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('generated_password');

        $secretary = User::where('email', 'secretary@calarian.gov.ph')->firstOrFail();
        $this->assertSame('admin', $secretary->role);
        $this->assertTrue($secretary->must_change_password);

        // Create staff with case_reviewer preset (Peace & Order / VAWC)
        $this->post(route('admin.users.store'), [
            'name' => 'Kagawad Peace & Order',
            'email' => 'kagawad.po@calarian.gov.ph',
            'role' => 'staff',
            'preset' => 'case_reviewer',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $kagawad = User::where('email', 'kagawad.po@calarian.gov.ph')->firstOrFail();
        $this->assertTrue($kagawad->hasPermission('cases.view_confidential'));
        $this->assertFalse($kagawad->hasPermission('cases.create'));

        // Update granular permissions directly
        $this->put(route('admin.users.permissions.update', $kagawad), [
            'permissions' => ['cases.view', 'reports_center.view'],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertFalse($kagawad->fresh()->hasPermission('cases.view_confidential'));
        $this->assertTrue($kagawad->fresh()->hasPermission('reports_center.view'));

        // With 2 active admins, deactivating secretary is allowed
        $this->post(route('admin.users.deactivate', $secretary))
            ->assertRedirect(route('admin.users.index'));
        $this->assertFalse($secretary->fresh()->is_active);

        // Captain is now the last active admin: demoting captain to staff is blocked
        $this->put(route('admin.users.update', $captain), [
            'name' => $captain->name,
            'email' => $captain->email,
            'role' => 'staff',
            'is_active' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertSame('admin', $captain->fresh()->role);
    }

    public function test_forced_password_change_flow_clears_must_change_password_flag(): void
    {
        $staff = User::factory()->create([
            'email' => 'newstaff@calarian.gov.ph',
            'password' => Hash::make('TempPass123!'),
            'role' => 'staff',
            'is_active' => true,
            'must_change_password' => true,
        ]);

        // EnsurePasswordChanged middleware redirects staff to account.password.edit
        $this->actingAs($staff)
            ->get(route('admin.reports-center.index'))
            ->assertRedirect(route('account.password.edit'));

        // Password identical to email is rejected
        $this->actingAs($staff)
            ->post(route('account.password.update'), [
                'password' => 'newstaff@calarian.gov.ph',
                'password_confirmation' => 'newstaff@calarian.gov.ph',
            ])->assertSessionHasErrors('password');

        // Valid 10+ char password clears must_change_password
        $this->actingAs($staff)
            ->post(route('account.password.update'), [
                'password' => 'StrongPermanentPass2026!',
                'password_confirmation' => 'StrongPermanentPass2026!',
            ])->assertRedirect(route('admin.dashboard'));

        $this->assertFalse($staff->fresh()->must_change_password);
        $this->assertTrue(Hash::check('StrongPermanentPass2026!', $staff->fresh()->password));
    }
}
