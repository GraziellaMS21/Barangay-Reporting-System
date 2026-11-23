<?php

namespace Tests\Feature\Setup;

use App\Models\FiscalYear;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_redirects_to_step_1_initially(): void
    {
        $this->withoutSetup();

        $response = $this->get('/setup');
        $response->assertRedirect(route('setup.step', ['step' => 1]));
    }

    public function test_step_1_verification_renders_and_proceeds(): void
    {
        $this->withoutSetup();

        $response = $this->get(route('setup.step', ['step' => 1]));
        $response->assertStatus(200);
        $response->assertSee('System Verification');

        $postResponse = $this->post(route('setup.store', ['step' => 1]));
        $postResponse->assertRedirect(route('setup.step', ['step' => 2]));
    }

    public function test_step_2_creates_admin_user(): void
    {
        $this->withoutSetup();

        $response = $this->post(route('setup.store', ['step' => 2]), [
            'name' => 'Hon. Admin Test',
            'email' => 'admin.test@calarian.gov.ph',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('setup.step', ['step' => 3]));
        $this->assertDatabaseHas('users', [
            'email' => 'admin.test@calarian.gov.ph',
            'role' => 'admin',
        ]);
    }

    public function test_step_3_saves_barangay_profile(): void
    {
        $this->withoutSetup();

        $response = $this->post(route('setup.store', ['step' => 3]), [
            'barangay_name' => 'Barangay Calarian Test',
            'city' => 'Zamboanga City Test',
            'province' => 'Zamboanga del Sur',
            'region' => 'Region IX',
            'hall_address' => 'Hall Street 123',
            'contact_number' => '0917-000-0000',
            'email' => 'contact@calarian.test',
        ]);

        $response->assertRedirect(route('setup.step', ['step' => 4]));
        $this->assertSame('Barangay Calarian Test', setting('barangay.name'));
    }

    public function test_step_4_creates_and_activates_fiscal_year(): void
    {
        $this->withoutSetup();

        $response = $this->post(route('setup.store', ['step' => 4]), [
            'year' => 2029,
        ]);

        $response->assertRedirect(route('setup.step', ['step' => 5]));

        $fy = FiscalYear::where('year', 2029)->first();
        $this->assertNotNull($fy);
        $this->assertSame('active', $fy->status);
    }

    public function test_step_5_finish_marks_setup_completed(): void
    {
        $this->withoutSetup();

        $response = $this->post(route('setup.finish'));
        $response->assertRedirect(route('login'));

        $setting = Setting::where('key', 'system.setup_completed')->first();
        $this->assertNotNull($setting);
        $this->assertTrue(filter_var($setting->value, FILTER_VALIDATE_BOOLEAN));
    }

    public function test_accessing_setup_after_completion_redirects_to_login(): void
    {
        $this->completeSetup();

        $response = $this->get('/setup');
        $response->assertRedirect(route('login'));
    }
}
