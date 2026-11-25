<?php

namespace Tests\Feature;

use App\Models\EmergencyContact;
use App\Models\Permission;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Purok;
use App\Models\Setting;
use App\Models\User;
use App\Services\GeminiService;
use App\Support\WorkingDays;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstRunImprovementsTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $perm = Permission::firstOrCreate(['slug' => 'settings.manage'], ['label' => 'Manage Settings', 'group' => 'settings']);
        $admin->permissions()->syncWithoutDetaching([$perm->id]);

        return $admin;
    }

    public function test_purok_crud_from_admin_ui(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('admin.puroks.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.puroks.store'), ['name' => 'Purok 8 - Maharlika'])
            ->assertRedirect(route('admin.puroks.index'));

        $purok = Purok::where('name', 'Purok 8 - Maharlika')->first();
        $this->assertNotNull($purok);

        $this->actingAs($admin)
            ->put(route('admin.puroks.update', $purok), ['name' => 'Sitio Maharlika'])
            ->assertRedirect(route('admin.puroks.index'));

        $this->assertDatabaseHas('puroks', ['id' => $purok->id, 'name' => 'Sitio Maharlika']);

        $this->actingAs($admin)
            ->delete(route('admin.puroks.destroy', $purok))
            ->assertRedirect(route('admin.puroks.index'));

        $this->assertDatabaseMissing('puroks', ['id' => $purok->id]);
    }

    public function test_working_days_can_be_configured_from_calendar_ui(): void
    {
        $admin = $this->adminUser();

        // Include Saturday (6) in working days
        $this->actingAs($admin)
            ->post(route('admin.calendar.working-days.update'), [
                'working_days' => [1, 2, 3, 4, 5, 6],
            ])
            ->assertRedirect(route('admin.calendar.index'));

        $this->assertSame('1,2,3,4,5,6', setting('cases.working_days'));
        // 2026-11-28 is a Saturday
        $this->assertTrue(WorkingDays::isWorkingDay('2026-11-28'));
        // 2026-11-29 is a Sunday
        $this->assertFalse(WorkingDays::isWorkingDay('2026-11-29'));
    }

    public function test_ai_model_and_encrypted_api_key_can_be_configured(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('admin.settings.ai.edit'))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('admin.settings.ai.update'), [
                'gemini_model' => 'gemini-2.5-pro',
                'gemini_api_key' => 'AIzaTestSecretKey12345',
            ])
            ->assertRedirect();

        $this->assertSame('gemini-2.5-pro', GeminiService::resolveModel());
        $this->assertSame('AIzaTestSecretKey12345', GeminiService::resolveApiKey());
        $this->assertNotSame('AIzaTestSecretKey12345', Setting::where('key', 'ai.gemini_api_key')->value('value'));
    }

    public function test_confirm_all_clears_pending_items(): void
    {
        $admin = $this->adminUser();

        Setting::create(['key' => 'barangay.test_unconfirmed', 'value' => 'foo', 'is_confirmed' => false]);
        Position::firstOrCreate(['code' => 'test_pos'], ['name' => 'Test Pos', 'is_confirmed' => false]);

        $this->actingAs($admin)
            ->post(route('admin.pending.confirm-all'))
            ->assertRedirect(route('admin.pending.index'));

        $this->assertSame(0, Setting::where('is_confirmed', false)->where('key', '!=', 'system.setup_completed')->count());
        $this->assertSame(0, Position::where('is_confirmed', false)->count());
    }

    public function test_setup_token_hotlines_and_auto_personnel_enrollment(): void
    {
        $this->withoutSetup();

        Setting::create([
            'key' => 'system.setup_token',
            'value' => 'SETUP-1234-ABCD',
            'type' => 'string',
            'group' => 'system',
            'is_confirmed' => true,
        ]);

        EmergencyContact::create(['name' => 'Police Station', 'number' => 'N/A', 'type' => 'police', 'is_active' => true, 'sort_order' => 2]);
        EmergencyContact::create(['name' => 'Fire Station', 'number' => 'N/A', 'type' => 'fire', 'is_active' => true, 'sort_order' => 3]);

        // Accessing step 2 without token redirects back to step 1
        $this->get(route('setup.step', ['step' => 2]))
            ->assertRedirect(route('setup.step', ['step' => 1]));

        // Wrong token fails
        $this->post(route('setup.store', ['step' => 1]), ['setup_token' => 'WRONG'])
            ->assertSessionHasErrors('setup_token');

        // Valid token succeeds
        $this->post(route('setup.store', ['step' => 1]), ['setup_token' => 'SETUP-1234-ABCD'])
            ->assertRedirect(route('setup.step', ['step' => 2]));

        // Step 2 creates admin
        $this->post(route('setup.store', ['step' => 2]), [
            'name' => 'Hon. Eduardo V. Alvarez',
            'email' => 'pb.alvarez@calarian.gov.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('setup.step', ['step' => 3]));

        // Step 3 saves hotlines
        $this->post(route('setup.store', ['step' => 3]), [
            'barangay_name' => 'Barangay Calarian',
            'city' => 'Zamboanga City',
            'hall_address' => 'Calarian Hall',
            'contact_number' => '0917-123-4567',
            'email' => 'info@calarian.gov.ph',
            'police_hotline' => '0919-777-7377',
            'fire_hotline' => '0917-555-0199',
        ])->assertRedirect(route('setup.step', ['step' => 4]));

        $this->assertSame('0919-777-7377', EmergencyContact::where('type', 'police')->value('number'));
        $this->assertSame('0917-555-0199', EmergencyContact::where('type', 'fire')->value('number'));

        // Finish auto-enrolls admin as Punong Barangay and consumes token
        $this->post(route('setup.finish'))->assertRedirect(route('login'));

        $admin = User::where('email', 'pb.alvarez@calarian.gov.ph')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Personnel::where('user_id', $admin->id)->where('is_signatory', true)->exists());
        $this->assertNull(Setting::where('key', 'system.setup_token')->first());
    }
}
