<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Staff sign in')
            ->assertSee(route('login'), false);
    }

    public function test_staff_can_sign_in_and_reach_the_dashboard(): void
    {
        $staff = User::factory()->staff()->create();

        $this->post(route('login'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.reports.index'));

        $this->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Staff Dashboard');
    }

    public function test_staff_can_open_report_dashboard_and_details(): void
    {
        $staff = User::factory()->staff()->create();
        $report = Report::factory()->create();

        $this->actingAs($staff)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Staff Dashboard')
            ->assertSee($report->tracking_code);

        $this->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee($report->tracking_code)
            ->assertSee('Update status');
    }
}
