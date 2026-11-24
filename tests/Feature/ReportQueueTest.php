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

        $inProgressResponse = $this->actingAs($staff)
            ->get(route('admin.reports.index', ['stage' => 'in_progress']))
            ->assertOk()
            ->assertSee('All')
            ->assertSee('Needs review')
            ->assertSee('In progress')
            ->assertSee('Waiting on others')
            ->assertSee('Done')
            ->assertSee('My cases')
            ->assertSee('Overdue')
            ->assertSee('Emergency')
            ->assertSee('Next Action')
            ->assertSee('id="mobile-stage"', false)
            ->assertSee($routedReport->tracking_code)
            ->assertSee($emergencyActiveReport->tracking_code)
            ->assertDontSee($newReport->tracking_code)
            ->assertDontSee($resolvedReport->tracking_code)
            ->assertDontSee($mergedReport->tracking_code);

        $this->assertSame(2, $inProgressResponse->viewData('reports')->total());
        $this->assertSame(2, $inProgressResponse->viewData('stageCounts')->get('in_progress'));
        $this->assertSame(9, $inProgressResponse->viewData('stageCounts')->get('all'));
        $this->assertSame(2, $inProgressResponse->viewData('emergencyCount'));

        $needsReviewResponse = $this->get(route('admin.reports.index', ['stage' => 'needs_review']))
            ->assertOk()
            ->assertSee($newReport->tracking_code)
            ->assertSee($submittedReport->tracking_code)
            ->assertDontSee($routedReport->tracking_code);

        $this->assertSame(2, $needsReviewResponse->viewData('reports')->total());

        $doneResponse = $this->get(route('admin.reports.index', ['stage' => 'done']))
            ->assertOk()
            ->assertSee($resolvedReport->tracking_code)
            ->assertSee($mergedReport->tracking_code)
            ->assertSee($noActionReport->tracking_code)
            ->assertDontSee($routedReport->tracking_code)
            ->assertDontSee($referredReport->tracking_code);

        $this->assertSame(3, $doneResponse->viewData('reports')->total());

        $waitingResponse = $this->get(route('admin.reports.index', ['stage' => 'waiting']))
            ->assertOk()
            ->assertSee($referredReport->tracking_code)
            ->assertDontSee($routedReport->tracking_code);

        $this->assertSame(1, $waitingResponse->viewData('reports')->total());

        $emergencyResponse = $this->get(route('admin.reports.index', [
            'stage' => 'in_progress',
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
            'stage' => 'done',
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

    public function test_compact_queue_rows_layout_and_priority_badges(): void
    {
        $staff = User::factory()->staff()->create([
            'name' => 'Hon. Eduardo V. Alvarez',
        ]);

        $criticalReport = Report::factory()->create([
            'tracking_code' => 'BRG-CRIT01',
            'status' => 'under_review',
            'urgency' => 'critical',
            'assigned_to' => $staff->id,
            'due_at' => now()->subHours(4),
        ]);

        $highReport = Report::factory()->create([
            'tracking_code' => 'BRG-HIGH01',
            'status' => 'submitted',
            'urgency' => 'high',
            'assigned_to' => null,
        ]);

        $mediumReport = Report::factory()->create([
            'tracking_code' => 'BRG-MED001',
            'status' => 'submitted',
            'urgency' => 'medium',
            'assigned_to' => null,
        ]);

        $lowReport = Report::factory()->create([
            'tracking_code' => 'BRG-LOW001',
            'status' => 'submitted',
            'urgency' => 'low',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($staff)
            ->get(route('admin.reports.index'))
            ->assertOk();

        // Row link target
        $response->assertSee(route('admin.reports.show', $criticalReport), false);
        $response->assertSee(route('admin.reports.show', $highReport), false);

        // Priority badges: rendered for High and Critical
        $response->assertSee('<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 uppercase tracking-wide shrink-0">Critical</span>', false);
        $response->assertSee('<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 uppercase tracking-wide shrink-0">High</span>', false);

        // Priority badges NOT shown for Medium or Low
        $response->assertDontSee('uppercase tracking-wide shrink-0">Medium</span>', false);
        $response->assertDontSee('uppercase tracking-wide shrink-0">Low</span>', false);

        // sr-only priority text on every row
        $response->assertSee('<span class="sr-only">Priority: Critical</span>', false);
        $response->assertSee('<span class="sr-only">Priority: High</span>', false);
        $response->assertSee('<span class="sr-only">Priority: Medium</span>', false);
        $response->assertSee('<span class="sr-only">Priority: Low</span>', false);

        // Row left stripe classes for Critical and High
        $response->assertSee('border-l-4 border-l-red-600', false);
        $response->assertSee('border-l-4 border-l-amber-500', false);

        // Initials fallback strips honorifics and limits to at most 2 characters ("Hon. Eduardo V. Alvarez" -> "EA", not "HEVA")
        $response->assertSee('>EA</span>', false);
        $response->assertDontSee('>HEVA</span>', false);

        // Unassigned avatar uses dash fallback
        $response->assertSee('>—</span>', false);

        // Kebab menu is present on each row with accessible toggle button
        $response->assertSee('aria-label="More actions for '.$criticalReport->tracking_code.'"', false);
        $response->assertSee('aria-label="More actions for '.$highReport->tracking_code.'"', false);

        // Overdue text rendered in Next Action cell
        $response->assertSee('title="Overdue"', false);
    }
}
