<?php

namespace Tests\Feature;

use App\Models\BarangayCase;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use Tests\TestCase;

class AppCommandsTest extends TestCase
{
    protected bool $seedSetup = false;

    public function test_app_empty_command_runs_successfully(): void
    {
        $this->artisan('app:empty', ['--force' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Clean installation complete');

        $this->assertTrue(Permission::count() > 0);
        $this->assertTrue(Position::count() > 0);
    }

    public function test_app_demo_command_runs_successfully(): void
    {
        $this->artisan('app:demo', ['--force' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Demo environment is ready');

        $this->assertTrue(User::where('email', 'admin@calarian.gov.ph')->exists());
        $this->assertTrue(BarangayCase::count() >= 4);
    }
}
