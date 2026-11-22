<?php

namespace Tests\Feature\Admin;

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalYearManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_admin_can_view_fiscal_years_list(): void
    {
        $response = $this->get(route('admin.fiscal-years.index'));
        $response->assertStatus(200);
        $response->assertSee('Fiscal Years Management');
    }

    public function test_admin_can_create_new_fiscal_year(): void
    {
        $response = $this->post(route('admin.fiscal-years.store'), [
            'year' => 2030,
            'activate' => 0,
        ]);

        $response->assertRedirect(route('admin.fiscal-years.index'));
        $this->assertDatabaseHas('fiscal_years', [
            'year' => 2030,
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_activate_fiscal_year(): void
    {
        $fy = FiscalYear::factory()->create(['year' => 2031, 'status' => 'inactive']);

        $response = $this->post(route('admin.fiscal-years.activate', $fy));
        $response->assertRedirect(route('admin.fiscal-years.index'));

        $this->assertSame('active', $fy->fresh()->status);
    }

    public function test_admin_can_close_fiscal_year(): void
    {
        $fy = FiscalYear::factory()->create(['year' => 2032, 'status' => 'active']);

        $response = $this->post(route('admin.fiscal-years.close', $fy), [
            'note' => 'Closing 2032 operations.',
        ]);

        $response->assertRedirect(route('admin.fiscal-years.index'));
        $fresh = $fy->fresh();
        $this->assertSame('closed', $fresh->status);
        $this->assertTrue($fresh->is_locked);
    }

    public function test_admin_can_reopen_fiscal_year(): void
    {
        $fy = FiscalYear::factory()->create(['year' => 2033, 'status' => 'closed', 'is_locked' => true]);

        $response = $this->post(route('admin.fiscal-years.reopen', $fy), [
            'note' => 'Reopening per directive.',
        ]);

        $response->assertRedirect(route('admin.fiscal-years.index'));
        $fresh = $fy->fresh();
        $this->assertSame('inactive', $fresh->status);
        $this->assertFalse($fresh->is_locked);
    }
}
