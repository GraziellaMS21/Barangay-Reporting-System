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

    public function test_resident_pages_render_with_the_shared_theme(): void
    {
        $this->get(route('reports.create'))
            ->assertOk()
            ->assertSee('Report a problem in Barangay Calarian')
            ->assertSee('aria-label="Open main navigation"', false)
            ->assertSee('aria-controls="site-navigation"', false)
            ->assertDontSee('fonts.googleapis.com')
            ->assertSee(asset('storage/Calarian_Barangay_Hall.jpg'), false)
            ->assertSee(asset('storage/calarian-seal.png'), false);

        $this->get(route('track.index'))
            ->assertOk()
            ->assertSee('Track a report');

        $report = Report::factory()->create();

        $this->get(route('reports.success', $report->tracking_code))
            ->assertOk()
            ->assertSee('Your tracking code');

        $this->get(route('track.show', $report->tracking_code))
            ->assertOk()
            ->assertSee($report->tracking_code)
            ->assertSee('Status history');
    }

    public function test_staff_can_sign_in_and_reach_the_dashboard(): void
    {
        $staff = User::factory()->staff()->create();

        $this->post(route('login'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Resident reports')
            ->assertSee('aria-label="Open staff navigation"', false)
            ->assertSee('aria-controls="staff-sidebar"', false)
            ->assertDontSee('fonts.googleapis.com');
    }

    public function test_admin_and_staff_have_distinct_navigation_and_settings_access(): void
    {
        $staff = User::factory()->staff()->create(['name' => 'Demo Staff']);
        $admin = User::factory()->admin()->create(['name' => 'Demo Admin']);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Staff Dashboard')
            ->assertSee('Demo Staff')
            ->assertSee('Staff')
            ->assertDontSee('Administration')
            ->assertDontSee('Emergency Contacts');

        $this->get(route('admin.settings.index'))->assertForbidden();
        $this->post(route('admin.settings.priority'), ['priority' => []])->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Dashboard')
            ->assertSee('Demo Admin')
            ->assertSee('Administration')
            ->assertSee('Emergency Contacts')
            ->assertSee('role-admin', false);

        $this->get(route('admin.settings.index'))->assertOk();
    }

    public function test_staff_can_open_report_dashboard_and_details(): void
    {
        $staff = User::factory()->staff()->create();
        $report = Report::factory()->create();

        $this->actingAs($staff)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Resident reports')
            ->assertSee($report->tracking_code);

        $this->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee($report->tracking_code)
            ->assertSee('Update status');
    }
}
