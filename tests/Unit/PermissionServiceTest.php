<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_service_sync_and_preset_application(): void
    {
        $service = app(PermissionService::class);
        $user = User::factory()->create(['role' => 'staff']);

        $perm1 = Permission::create(['slug' => 'cases.view', 'label' => 'View Cases', 'group' => 'cases']);
        $perm2 = Permission::create(['slug' => 'cases.create', 'label' => 'Create Cases', 'group' => 'cases']);
        $perm3 = Permission::create(['slug' => 'settings.manage', 'label' => 'Settings Manage', 'group' => 'settings']);

        $service->sync($user, ['cases.view']);
        $this->assertTrue($user->hasPermission('cases.view'));
        $this->assertFalse($user->hasPermission('cases.create'));

        // Apply preset
        $service->applyPreset($user, 'case_encoder');
        $this->assertTrue($user->hasPermission('cases.view'));
        $this->assertTrue($user->hasPermission('cases.create'));
        $this->assertFalse($user->hasPermission('settings.manage'));
    }

    public function test_admin_implicitly_has_all_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($admin->hasPermission('cases.view'));
        $this->assertTrue($admin->hasPermission('arbitrary.nonexistent.permission'));
    }
}
