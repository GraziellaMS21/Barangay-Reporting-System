<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_groups_lifecycle_statuses_and_filters_emergencies_independently(): void
    {
        $staff = User::factory()->staff()->create();
        $newReport = Report::factory()->create([
            'tracking_code' => 'BRG-NEW001',
            'status' => 'under_review',
        ]);
        $submittedReport = Report::factory()->create([
            'tracking_code' => 'BRG-SUBMIT1',
            'status' => 'submitted',
        ]);
        $routedReport = Report::factory()->create([
            'tracking_code' => 'BRG-ROUTED1',
            'status' => 'routed',
        ]);
        $emergencyActiveReport = Report::factory()->create([
            'tracking_code' => 'BRG-EMERG01',
            'status' => 'in_progress',
            'is_emergency' => true,
        ]);
        $resolvedReport = Report::factory()->create([
            'tracking_code' => 'BRG-RESOLVED',
            'status' => 'resolved',
        ]);
        $referredReport = Report::factory()->create([
            'tracking_code' => 'BRG-REFERRED',
            'status' => 'referred',
        ]);
        $mergedReport = Report::factory()->create([
            'tracking_code' => 'BRG-MERGED1',
            'status' => 'merged',
        ]);
        $noActionReport = Report::factory()->create([
            'tracking_code' => 'BRG-SCOPE001',
            'status' => 'no_action',
            'no_action_reason' => 'not_barangay_matter',
        ]);
        $legacyEmergencyReport = Report::factory()->create([
            'tracking_code' => 'BRG-LEGACY1',
            'status' => 'emergency_flagged',
            'is_emergency' => true,
        ]);

        $activeResponse = $this->actingAs($staff)
            ->get(route('admin.reports.index', ['stage' => 'active']))
            ->assertOk()
            ->assertSee('All')
            ->assertSee('New')
            ->assertSee('Active')
            ->assertSee('Resolved')
            ->assertSee('Others')
            ->assertSee('Emergency')
            ->assertSee('Routed')
            ->assertSee('In Progress')
            ->assertSee('id="mobile-stage"', false)
            ->assertSee($routedReport->tracking_code)
            ->assertSee($emergencyActiveReport->tracking_code)
            ->assertDontSee($newReport->tracking_code)
            ->assertDontSee($resolvedReport->tracking_code)
            ->assertDontSee($mergedReport->tracking_code);

        $this->assertSame(2, $activeResponse->viewData('reports')->total());
        $this->assertSame(2, $activeResponse->viewData('stageCounts')->get('active'));
        $this->assertSame(9, $activeResponse->viewData('stageCounts')->get('all'));
        $this->assertSame(2, $activeResponse->viewData('emergencyCount'));

        $newResponse = $this->get(route('admin.reports.index', ['stage' => 'new']))
            ->assertOk()
            ->assertSee($newReport->tracking_code)
            ->assertSee($submittedReport->tracking_code)
            ->assertDontSee($routedReport->tracking_code)
            ->assertDontSee('Show:');

        $this->assertSame(2, $newResponse->viewData('reports')->total());

        $resolvedResponse = $this->get(route('admin.reports.index', ['stage' => 'resolved']))
            ->assertOk()
            ->assertSee($resolvedReport->tracking_code)
            ->assertDontSee($mergedReport->tracking_code);

        $this->assertSame(1, $resolvedResponse->viewData('reports')->total());

        $closedOtherResponse = $this->get(route('admin.reports.index', ['stage' => 'closed-other']))
            ->assertOk()
            ->assertSee('Referred')
            ->assertSee('Merged')
            ->assertSee('No Action')
            ->assertSee($referredReport->tracking_code)
            ->assertSee($mergedReport->tracking_code)
            ->assertSee($noActionReport->tracking_code)
            ->assertDontSee($resolvedReport->tracking_code);

        $this->assertSame(3, $closedOtherResponse->viewData('reports')->total());

        $emergencyResponse = $this->get(route('admin.reports.index', [
            'stage' => 'active',
            'emergency' => '1',
        ]))->assertOk()
            ->assertSee($emergencyActiveReport->tracking_code)
            ->assertDontSee($routedReport->tracking_code)
            ->assertDontSee($legacyEmergencyReport->tracking_code);

        $this->assertSame(1, $emergencyResponse->viewData('reports')->total());

        $allEmergenciesResponse = $this->get(route('admin.reports.index', [
            'stage' => 'all',
            'emergency' => '1',
        ]))->assertOk()
            ->assertSee($emergencyActiveReport->tracking_code)
            ->assertSee($legacyEmergencyReport->tracking_code)
            ->assertDontSee($routedReport->tracking_code);

        $this->assertSame(2, $allEmergenciesResponse->viewData('reports')->total());

        $mergedResponse = $this->get(route('admin.reports.index', [
            'stage' => 'closed-other',
            'status' => 'merged',
        ]))->assertOk()
            ->assertSee($mergedReport->tracking_code)
            ->assertDontSee($resolvedReport->tracking_code)
            ->assertDontSee($newReport->tracking_code);

        $this->assertSame(1, $mergedResponse->viewData('reports')->total());
    }

    public function test_queue_keeps_the_emergency_filter_visible_when_there_are_no_emergencies(): void
    {
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Emergency')
            ->assertSee('aria-pressed="false"', false);

        $this->assertSame(0, $response->viewData('emergencyCount'));
    }
}
