<?php

namespace Tests;

use App\Models\AgencyRegistration;
use App\Models\FiscalYear;
use App\Models\Office;
use App\Models\Setting;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected bool $seedSetup = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->seedSetup) {
            $this->completeSetup();
        }
    }

    public function withoutSetup(): static
    {
        $this->seedSetup = false;
        config(['app.setup_completed' => false]);
        Setting::where('key', 'system.setup_completed')->delete();
        Cache::forget('setting.system.setup_completed');

        return $this;
    }

    public function completeSetup(): void
    {
        config(['app.setup_completed' => true]);

        $year = (int) date('Y');
        FiscalYear::firstOrCreate(
            ['year' => $year],
            [
                'label' => "FY {$year}",
                'starts_on' => "{$year}-01-01",
                'ends_on' => "{$year}-12-31",
                'status' => 'active',
                'is_locked' => false,
            ]
        );
    }

    public function actingAsAdmin(?User $user = null): static
    {
        $user ??= User::factory()->create(['role' => 'admin', 'is_active' => true]);

        return $this->actingAs($user);
    }

    public function actingAsStaff(?array $permissions = null): static
    {
        $user = User::factory()->create(['role' => 'staff', 'is_active' => true]);

        if ($permissions === null) {
            try {
                app(PermissionService::class)->applyPreset($user, 'staff_full');
            } catch (\Throwable) {
                // Ignore if presets/permissions not yet seeded in isolated tests
            }
        } else {
            app(PermissionService::class)->sync($user, $permissions);
        }

        return $this->actingAs($user);
    }

    public function actingAsEncoder(): static
    {
        $user = User::factory()->create(['role' => 'staff', 'is_active' => true]);

        try {
            app(PermissionService::class)->applyPreset($user, 'case_encoder');
        } catch (\Throwable) {
            // Ignore if permissions not yet seeded
        }

        return $this->actingAs($user);
    }

    public function actingAsAgency(?Office $office = null, string $status = 'approved'): static
    {
        $office ??= Office::first() ?? Office::create([
            'name' => 'City Social Welfare and Development Office',
            'type' => 'welfare',
            'is_internal' => false,
        ]);

        $user = User::factory()->create([
            'role' => 'agency',
            'office_id' => $office->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        AgencyRegistration::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'agency_name_claimed' => $office->name,
            'position_title' => 'Liaison Officer',
            'contact_number' => '09171234567',
            'id_document_path' => 'documents/id.jpg',
            'status' => $status,
            'is_office_admin' => false,
        ]);

        return $this->actingAs($user);
    }
}
