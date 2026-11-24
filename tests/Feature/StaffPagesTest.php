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
            ->assertSee('Unique Tracking Code');

        $this->get(route('track.show', $report->tracking_code))
            ->assertOk()
            ->assertSee($report->tracking_code)
            ->assertSee('Status History');
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
            ->assertSee('Review AI suggestions')
            ->assertSee('Confirm and route');
    }

    public function test_staff_map_requires_staff_authentication_and_renders_hotspot_controls(): void
    {
        $report = Report::factory()->create([
            'lat' => 6.918123,
            'lng' => 122.081456,
            'reporter_name' => 'Private Demo Resident',
            'reporter_contact' => '09170000001',
        ]);
        Report::factory()->create([
            'lat' => 6.918321,
            'lng' => 122.081234,
        ]);

        $this->get(route('admin.map'))
            ->assertRedirect(route('login'));

        $response = $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.map'))
            ->assertOk()
            ->assertSee('name="category-filter"', false)
            ->assertSee('name="purok-filter"', false)
            ->assertSee('name="status-filter"', false)
            ->assertSee('name="date-from"', false)
            ->assertSee('name="date-to"', false)
            ->assertSee('Show all reports')
            ->assertSee('Clear hotspot')
            ->assertSee('staff-map-insights', false)
            ->assertSee('data-hotspot-cell="6.918,122.081"', false)
            ->assertSee('<strong>2</strong> reports', false)
            ->assertSee($report->tracking_code)
            ->assertViewHas('mapReports', function ($mapReports) use ($report): bool {
                $mappedReport = $mapReports->firstWhere('trackingCode', $report->tracking_code);

                $this->assertSame(route('admin.reports.show', $report), $mappedReport['url'] ?? null);

                return true;
            })
            ->assertViewHas('weeklyTrend', function (array $weeklyTrend): bool {
                $this->assertCount(8, $weeklyTrend);
                $this->assertSame(2, array_sum(array_column($weeklyTrend, 'total')));

                return true;
            })
            ->assertDontSee('Private Demo Resident')
            ->assertDontSee('09170000001');

        $page = $response->getContent();
        $mapCardPosition = strpos($page, 'class="card staff-map-card"');
        $filtersEndPosition = strpos($page, '</aside>', strpos($page, 'class="card staff-map-controls"'));
        $hotspotsCardPosition = strpos($page, 'class="card staff-map-insights"');

        $this->assertIsInt($mapCardPosition);
        $this->assertIsInt($filtersEndPosition);
        $this->assertIsInt($hotspotsCardPosition);
        $this->assertGreaterThan($filtersEndPosition, $mapCardPosition);
        $this->assertGreaterThan($mapCardPosition, $hotspotsCardPosition);
    }

    public function test_authenticated_layouts_include_scoped_turbo_controls(): void
    {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        // Admin & Staff layouts have turbo-cache-control, data-turbo="true", permanent sidebar, and non-turbo logout
        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<meta name="turbo-cache-control" content="no-cache">', false)
            ->assertSee('<meta name="turbo-prefetch" content="false">', false)
            ->assertSee('data-turbo="true"', false)
            ->assertSee('id="staff-sidebar"', false)
            ->assertSee('data-turbo-permanent', false)
            ->assertSee('action="'.route('logout').'" data-turbo="false"', false);

        // Export links have data-turbo="false"
        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('/admin/reports/export')
            ->assertSee('data-turbo="false"', false);

        $this->actingAs($admin)
            ->get(route('admin.cases.index'))
            ->assertOk()
            ->assertSee(route('admin.cases.export-csv'))
            ->assertSee('data-turbo="false"', false);

        // Public and resident pages must NOT have Turbo enabled
        $this->get(route('reports.create'))
            ->assertOk()
            ->assertDontSee('name="turbo-cache-control"', false)
            ->assertDontSee('data-turbo="true"', false);
    }

    public function test_sidebar_does_not_contain_my_cases_and_report_queue_stays_active(): void
    {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        // Staff layout does not show My Cases in sidebar
        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('title="My Cases"', false)
            ->assertDontSee('href="'.route('admin.reports.my-cases').'"', false);

        // Admin layout does not show My Cases in sidebar
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('title="My Cases"', false)
            ->assertDontSee('href="'.route('admin.reports.my-cases').'"', false);

        // On reports queue with my_cases filter, Report Queue is highlighted
        $response = $this->actingAs($staff)
            ->get(route('admin.reports.index', ['my_cases' => 1]))
            ->assertOk();

        $response->assertSee('title="Community reports"', false);
    }

    public function test_queue_filters_component_renders_tabs_chips_and_scoped_counts(): void
    {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        // 2 reports assigned to staff, 1 assigned to admin
        Report::factory()->create([
            'assigned_to' => $staff->id,
            'status' => 'submitted',
        ]);
        Report::factory()->create([
            'assigned_to' => $staff->id,
            'status' => 'in_progress',
        ]);
        Report::factory()->create([
            'assigned_to' => $admin->id,
            'status' => 'submitted',
        ]);

        // When viewing general queue: tab All count is 3
        $generalResponse = $this->actingAs($staff)
            ->get(route('admin.reports.index'))
            ->assertOk();

        $generalResponse->assertSee('role="tablist"', false)
            ->assertSee('All')
            ->assertSee('Needs review')
            ->assertSee('In progress')
            ->assertSee('Waiting on others')
            ->assertSee('Done')
            ->assertSee('My cases')
            ->assertSee('Overdue')
            ->assertSee('Emergency');

        // When filtering by my_cases=1: counts scope to current user's 2 reports
        $myCasesResponse = $this->actingAs($staff)
            ->get(route('admin.reports.index', ['my_cases' => 1]))
            ->assertOk();

        $myCasesResponse->assertSee('role="tablist"', false)
            ->assertSee('aria-pressed="true"', false);
    }
}
