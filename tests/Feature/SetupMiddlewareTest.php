<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SetupMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'setup.complete'])->get('/test-setup-target-check', fn () => 'target-page')->name('test.target.check');
        Route::middleware(['web'])->get('/setup', fn () => 'setup-page')->name('setup.show');
    }

    public function test_setup_complete_middleware_redirects_when_incomplete(): void
    {
        $this->withoutSetup();

        $response = $this->get('/test-setup-target-check');
        $response->assertRedirect('/setup');

        // Excluded route /setup passes through
        $this->get('/setup')->assertOk();
    }

    public function test_setup_complete_middleware_allows_access_and_redirects_setup_when_complete(): void
    {
        $response = $this->get('/setup');
        $response->assertRedirect('/login');

        $this->get('/test-setup-target-check')->assertOk();
    }
}
