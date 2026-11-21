<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_set_and_helper_functions(): void
    {
        $settings = app(SettingsService::class);
        $admin = User::factory()->create(['role' => 'admin']);

        // Default value
        $this->assertSame('fallback_val', $settings->get('nonexistent.key', 'fallback_val'));
        $this->assertSame('fallback_val', setting('nonexistent.key', 'fallback_val'));

        // Set
        $settings->set('test.key', 'hello_world', $admin);
        $this->assertSame('hello_world', $settings->get('test.key'));
        $this->assertSame('hello_world', setting('test.key'));

        // Typed getters
        $settings->set('flag.active', true, $admin);
        $this->assertTrue($settings->getBool('flag.active'));

        $settings->set('numeric.val', 99, $admin);
        $this->assertSame(99, $settings->getInt('numeric.val'));
    }

    public function test_set_many_and_placeholder_confirmation_tracking(): void
    {
        $settings = app(SettingsService::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $settingRecord = Setting::create([
            'key' => 'barangay.name',
            'value' => 'Barangay Calarian',
            'type' => 'string',
            'group' => 'general',
            'label' => 'Barangay Name',
            'is_confirmed' => false,
        ]);

        $this->assertSame(1, $settings->unconfirmed()->count());

        $settings->setMany([
            'barangay.name' => 'Barangay Calarian Proper',
        ], $admin);

        $this->assertSame('Barangay Calarian Proper', $settings->get('barangay.name'));

        // Confirm
        $settings->confirm('barangay.name');
        $this->assertSame(0, $settings->unconfirmed()->count());
        $this->assertTrue($settingRecord->fresh()->is_confirmed);
    }
}
