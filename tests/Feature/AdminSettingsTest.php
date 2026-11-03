<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Office;
use App\Models\Report;
use App\Models\RoutingRule;
use App\Models\SensitivePlace;
use App\Models\Setting;
use App\Models\StatusLog;
use App\Models\User;
use App\Services\PriorityService;
use App\Services\RoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_and_guests_cannot_open_admin_settings_or_audit_pages(): void
    {
        $staff = User::factory()->staff()->create();

        $this->get(route('admin.settings.users.index'))
            ->assertRedirect(route('login'));
        $this->get(route('admin.audit-log.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($staff)
            ->get(route('admin.settings.users.index'))
            ->assertForbidden();
        $this->get(route('admin.audit-log.index'))
            ->assertForbidden();
        $this->post(route('admin.settings.priority'), ['priority' => []])
            ->assertForbidden();
    }

    public function test_admin_settings_routes_render_their_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->is_active);
        $category = Category::query()->create([
            'name' => 'Road damage',
            'slug' => 'road-damage',
            'risk_weight' => 3,
            'is_active' => true,
        ]);
        $office = Office::query()->create([
            'name' => 'City Engineering',
            'type' => 'City office',
            'is_internal' => false,
        ]);
        $routingRule = RoutingRule::query()->create([
            'category_id' => $category->id,
            'office_id' => $office->id,
            'within_authority' => false,
            'jurisdiction_note' => 'City responsibility.',
        ]);
        $sensitivePlace = SensitivePlace::query()->create([
            'name' => 'Calarian Elementary School',
            'type' => 'school',
            'lat' => 6.9181234,
            'lng' => 122.0812345,
        ]);
        $staff = User::factory()->staff()->create();

        $routes = [
            'admin.settings.index',
            'admin.settings.categories.index',
            route('admin.settings.categories.create'),
            route('admin.settings.categories.edit', $category),
            'admin.settings.offices.index',
            route('admin.settings.offices.create'),
            route('admin.settings.offices.edit', $office),
            'admin.settings.routing-rules.index',
            route('admin.settings.routing-rules.create'),
            route('admin.settings.routing-rules.edit', $routingRule),
            'admin.settings.sensitive-places.index',
            route('admin.settings.sensitive-places.create'),
            route('admin.settings.sensitive-places.edit', $sensitivePlace),
            'admin.settings.users.index',
            route('admin.settings.users.create'),
            route('admin.settings.users.edit', $staff),
            route('admin.settings.users.edit', $admin),
            'admin.settings.priority-weights.index',
            'admin.settings.emergency-alert-emails.index',
            'admin.audit-log.index',
        ];

        $this->actingAs($admin);

        foreach ($routes as $route) {
            $this->get(str_starts_with($route, 'http') ? $route : route($route))->assertOk();
        }
    }

    public function test_admin_can_create_and_update_categories_with_audit_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $office = Office::query()->create([
            'name' => 'Drainage Committee',
            'type' => 'Barangay committee',
            'is_internal' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.categories.store'), [
                'name' => 'Invalid category',
                'risk_weight' => '1001',
                'default_office_id' => '',
                'is_active' => '1',
            ])->assertSessionHasErrors(['risk_weight']);

        $this->assertDatabaseCount('categories', 0);

        $this->post(route('admin.settings.categories.store'), [
            'name' => 'Drainage and flooding',
            'risk_weight' => '12',
            'default_office_id' => (string) $office->id,
            'is_active' => '1',
        ])
            ->assertRedirect(route('admin.settings.categories.index'))
            ->assertSessionHas('success', 'Category added.');

        $category = Category::query()->where('slug', 'drainage-and-flooding')->firstOrFail();
        $this->assertModelExists($category);
        $this->assertSame(12, $category->risk_weight);
        $this->assertSame($office->id, $category->default_office_id);
        $this->assertSame($admin->id, AuditLog::query()->firstOrFail()->user_id);

        $this->put(route('admin.settings.categories.update', $category), [
            'name' => 'Drainage',
            'risk_weight' => '18',
            'default_office_id' => '',
            'is_active' => '0',
        ])->assertRedirect(route('admin.settings.categories.index'))
            ->assertSessionHas('success', 'Category updated.');

        $this->assertSame('drainage', $category->fresh()->slug);
        $this->assertSame(18, $category->fresh()->risk_weight);
        $this->assertFalse($category->fresh()->is_active);
        $updatedAudit = AuditLog::query()->where('action', 'updated')->firstOrFail();
        $this->assertSame('drainage-and-flooding', $updatedAudit->old_values['slug']);
        $this->assertSame('drainage', $updatedAudit->new_values['slug']);
    }

    public function test_admin_routing_rule_changes_the_recommended_office_and_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create([
            'name' => 'Road damage',
            'slug' => 'road-damage',
            'risk_weight' => 3,
            'is_active' => true,
        ]);
        $office = Office::query()->create([
            'name' => 'City Engineering',
            'type' => 'City office',
            'is_internal' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.routing-rules.store'), [
                'category_id' => (string) $category->id,
                'office_id' => (string) $office->id,
                'within_authority' => '0',
                'jurisdiction_note' => 'Refer city-maintained roads to City Engineering.',
            ])
            ->assertRedirect(route('admin.settings.routing-rules.index'))
            ->assertSessionHas('success', 'Routing rule added.');

        $routingRule = RoutingRule::query()->firstOrFail();
        $this->assertSame($office->id, app(RoutingService::class)->recommendedOffice($category)->id);
        $this->assertFalse($routingRule->within_authority);
        $this->assertSame('Refer city-maintained roads to City Engineering.', $routingRule->jurisdiction_note);

        $this->put(route('admin.settings.routing-rules.update', $routingRule), [
            'category_id' => (string) $category->id,
            'office_id' => (string) $office->id,
            'within_authority' => '1',
            'jurisdiction_note' => 'Handled by the barangay.',
        ])->assertRedirect(route('admin.settings.routing-rules.index'));

        $updatedAudit = AuditLog::query()->where('action', 'updated')->firstOrFail();
        $this->assertSame(false, $updatedAudit->old_values['within_authority']);
        $this->assertSame(true, $updatedAudit->new_values['within_authority']);
    }

    public function test_admin_can_manage_offices_and_cannot_delete_an_office_in_use(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.offices.store'), [
                'name' => 'Barangay Engineering',
                'type' => 'Barangay committee',
                'is_internal' => '1',
                'contact' => 'Local 12',
            ])
            ->assertRedirect(route('admin.settings.offices.index'));

        $office = Office::query()->where('name', 'Barangay Engineering')->firstOrFail();
        $category = Category::query()->create([
            'name' => 'Streetlight',
            'slug' => 'streetlight',
            'risk_weight' => 2,
            'default_office_id' => $office->id,
            'is_active' => true,
        ]);

        $this->delete(route('admin.settings.offices.destroy', $office))
            ->assertSessionHasErrors(['office']);
        $this->assertModelExists($office);

        $category->update(['default_office_id' => null]);
        $this->delete(route('admin.settings.offices.destroy', $office))
            ->assertRedirect(route('admin.settings.offices.index'))
            ->assertSessionHas('success', 'Office deleted.');

        $this->assertDatabaseMissing('offices', ['id' => $office->id]);
        $this->assertSame('deleted', AuditLog::query()->latest('id')->value('action'));
    }

    public function test_sensitive_place_coordinates_are_validated_and_changes_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->post(route('admin.settings.sensitive-places.store'), [
            'name' => 'Calarian Elementary School',
            'type' => 'school',
            'lat' => '91',
            'lng' => '122.08',
        ])->assertSessionHasErrors(['lat' => 'Latitude must be between -90 and 90.']);

        $this->assertDatabaseCount('sensitive_places', 0);

        $this->post(route('admin.settings.sensitive-places.store'), [
            'name' => 'Calarian Elementary School',
            'type' => 'school',
            'lat' => '6.9181234',
            'lng' => '122.0812345',
        ])->assertRedirect(route('admin.settings.sensitive-places.index'));

        $place = SensitivePlace::query()->firstOrFail();
        $this->assertSame(6.9181234, (float) $place->lat);
        $this->assertSame('created', AuditLog::query()->firstOrFail()->action);
    }

    public function test_admin_can_create_and_deactivate_users_and_cannot_deactivate_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.users.store'), [
                'name' => 'Calarian Staff',
                'email' => 'calarian.staff@example.test',
                'role' => 'staff',
                'password' => 'resident-demo-password',
                'password_confirmation' => 'resident-demo-password',
            ])
            ->assertRedirect(route('admin.settings.users.index'))
            ->assertSessionHas('success', 'User account created.');

        $managedUser = User::query()->where('email', 'calarian.staff@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('resident-demo-password', $managedUser->password));
        $createdAudit = AuditLog::query()->where('model', User::class)->firstOrFail();
        $this->assertArrayNotHasKey('password', $createdAudit->new_values);

        $this->put(route('admin.settings.users.update', $managedUser), [
            'name' => $managedUser->name,
            'email' => $managedUser->email,
            'role' => 'admin',
            'is_active' => '1',
        ])->assertRedirect(route('admin.settings.users.index'));

        $this->assertTrue($managedUser->fresh()->isAdmin());

        $this->put(route('admin.settings.users.update', $managedUser), [
            'name' => $managedUser->name,
            'email' => $managedUser->email,
            'role' => 'staff',
            'is_active' => '0',
        ])->assertRedirect(route('admin.settings.users.index'));

        $this->assertFalse($managedUser->fresh()->is_active);

        $this->put(route('admin.settings.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'admin',
            'is_active' => '0',
        ])->assertSessionHasErrors(['role']);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_inactive_users_cannot_sign_in_or_access_staff_pages(): void
    {
        $inactiveStaff = User::factory()->staff()->create(['is_active' => false]);

        $this->post(route('login'), [
            'email' => $inactiveStaff->email,
            'password' => 'password',
        ])->assertSessionHasErrors(['email' => 'Invalid email or password.']);

        $this->actingAs($inactiveStaff)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_priority_weights_and_emergency_alert_recipients_are_validated_and_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $weights = [
            'urgency' => '25',
            'category' => '12',
            'sensitive_place' => '30',
            'supporting_reports' => '9',
            'age_days' => '3',
        ];
        $this->post(route('admin.settings.priority'), [
            'priority' => [...$weights, 'urgency' => '1001'],
        ])->assertSessionHasErrors(['priority.urgency']);

        $this->assertSame(0, Setting::query()->count());

        $this->post(route('admin.settings.priority'), ['priority' => $weights])
            ->assertSessionHas('success', 'Priority weights saved.');

        $this->assertSame(25, app(PriorityService::class)->weights()['urgency']);
        $this->post(route('admin.settings.priority-weights.reset'))
            ->assertSessionHas('success', 'Priority weights reset to their defaults.');
        $this->assertSame(PriorityService::DEFAULT_WEIGHTS, app(PriorityService::class)->weights());
        $this->assertSame(10, AuditLog::query()->where('model', Setting::class)->count());

        $this->put(route('admin.settings.emergency-alert-emails.update'), [
            'email_addresses' => "Captain@example.test\ncaptain@example.test, tanod@example.test",
        ])->assertSessionHas('success', 'Emergency alert email recipients saved.');

        $this->assertSame(
            'Captain@example.test'.PHP_EOL.'tanod@example.test',
            Setting::value('emergency_alert_emails')
        );

        $this->followingRedirects()
            ->put(route('admin.settings.emergency-alert-emails.update'), [
                'email_addresses' => 'not-an-email',
            ])->assertOk()
            ->assertSee('Each alert address must be a valid email address.');
    }

    public function test_admin_audit_log_filters_setting_changes_and_displays_status_history(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $report = Report::factory()->create();
        AuditLog::query()->create([
            'user_id' => $staff->id,
            'action' => 'ai_override',
            'model' => 'App\\Models\\ReportAiResult',
            'model_id' => 18,
            'old_values' => ['suggested_urgency' => 'Medium'],
            'new_values' => ['suggested_urgency' => 'High'],
        ]);
        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'updated',
            'model' => Category::class,
            'model_id' => 2,
            'old_values' => ['risk_weight' => 1],
            'new_values' => ['risk_weight' => 5],
        ]);
        StatusLog::query()->create([
            'report_id' => $report->id,
            'user_id' => $staff->id,
            'from_status' => 'submitted',
            'to_status' => 'under_review',
            'note' => 'Staff started review.',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.audit-log.filter'), [
                'action' => 'ai_override',
                'user_id' => (string) $staff->id,
            ])
            ->assertRedirect(route('admin.audit-log.index', [
                'action' => 'ai_override',
                'user_id' => $staff->id,
            ]));

        $this->get(route('admin.audit-log.index', [
            'action' => 'ai_override',
            'user_id' => $staff->id,
        ]))->assertOk()
            ->assertSee('Medium')
            ->assertSee('High')
            ->assertSee($report->tracking_code)
            ->assertSee('Staff started review.')
            ->assertDontSee('risk_weight');
    }
}
