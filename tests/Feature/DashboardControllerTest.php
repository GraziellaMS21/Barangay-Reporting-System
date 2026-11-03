<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DuplicateSuggestion;
use App\Models\Office;
use App\Models\Purok;
use App\Models\Referral;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_metrics_charts_and_pending_items_in_tables(): void
    {
        $staff = User::factory()->staff()->create();
        $priorityReport = Report::factory()->create([
            'status' => 'in_progress',
            'urgency' => 'High',
            'priority_score' => 80,
        ]);
        Report::factory()->create([
            'status' => 'resolved',
            'created_at' => now()->subHours(6),
            'resolved_at' => now(),
        ]);
        $unreviewedReport = Report::factory()->create(['status' => 'submitted']);
        $candidateReport = Report::factory()->create(['status' => 'under_review']);
        $emergencyReport = Report::factory()->create(['status' => 'emergency_flagged']);
        $office = Office::create([
            'name' => 'City Engineering',
            'type' => 'city',
            'is_internal' => false,
        ]);
        DuplicateSuggestion::create([
            'report_id' => $unreviewedReport->id,
            'candidate_id' => $candidateReport->id,
            'score' => 0.85,
            'decision' => 'pending',
        ]);
        Referral::create([
            'report_id' => $priorityReport->id,
            'office_id' => $office->id,
            'referral_text' => 'Please assess this concern.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('data-dashboard-tabs', false)
            ->assertSeeInOrder([
                'Operational metrics',
                'Reports by category',
                '5 most recent reports',
                'Top 5 priority reports',
                'Still unreviewed',
                'Pending duplicate suggestions',
                'Pending referrals',
            ])
            ->assertSee('Top 5 priority reports')
            ->assertSee($priorityReport->tracking_code)
            ->assertSee('Still unreviewed')
            ->assertSee($unreviewedReport->tracking_code)
            ->assertSee('Pending duplicate suggestions')
            ->assertSee($candidateReport->tracking_code)
            ->assertSee('Pending referrals')
            ->assertSee($office->name)
            ->assertSee('6.0 h')
            ->assertSee('emergency report');

        $this->assertSame(5, substr_count($response->getContent(), '<table'));
    }

    public function test_dashboard_sends_only_aggregate_counts_to_gemini(): void
    {
        $staff = User::factory()->staff()->create();
        Report::factory()->create([
            'reporter_name' => 'Private Demo Name',
            'reporter_contact' => '09171234567',
            'reporter_email' => 'private@example.test',
            'description' => 'Private description for dashboard test.',
        ]);
        config([
            'services.gemini.key' => 'test-key',
            'services.gemini.model' => 'gemini-test',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => '{"insights":["Review incoming reports promptly."]}']],
                    ],
                ]],
            ]),
        ]);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Review incoming reports promptly.');

        $payload = null;
        Http::assertSent(function (Request $request) use (&$payload): bool {
            $payload = $request->data();

            return true;
        });

        $requestBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('Private Demo Name', $requestBody);
        $this->assertStringNotContainsString('09171234567', $requestBody);
        $this->assertStringNotContainsString('private@example.test', $requestBody);
        $this->assertStringNotContainsString('Private description for dashboard test.', $requestBody);
    }

    public function test_dashboard_shows_friendly_insights_fallback_when_gemini_is_not_configured(): void
    {
        $staff = User::factory()->staff()->create();
        config(['services.gemini.key' => '']);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Insights are temporarily unavailable.');
    }

    public function test_dashboard_charts_show_empty_states_when_there_are_no_reports(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Reports by category')
            ->assertSee('Reports by status')
            ->assertSee('Reports per week')
            ->assertSee('Reports by purok')
            ->assertSee('Urgency breakdown')
            ->assertSee('No reports yet.');
    }

    public function test_dashboard_chart_data_excludes_emergency_reports_from_category_and_urgency(): void
    {
        $staff = User::factory()->staff()->create();
        $category = Category::create([
            'name' => 'Drainage',
            'slug' => 'drainage',
            'risk_weight' => 1,
            'is_active' => true,
        ]);
        $purok = Purok::factory()->create(['name' => 'Purok 4']);
        Report::factory()->create([
            'category_id' => $category->id,
            'purok_id' => $purok->id,
            'status' => 'submitted',
            'urgency' => 'High',
        ]);
        Report::factory()->create([
            'category_id' => $category->id,
            'purok_id' => $purok->id,
            'status' => 'emergency_flagged',
            'urgency' => 'Critical',
            'is_emergency' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('chartData', function (array $chartData): bool {
                $this->assertSame(['Drainage'], $chartData['category']['labels']);
                $this->assertSame([1], $chartData['category']['counts']);
                $this->assertSame(1, $chartData['status']['counts'][array_search(
                    'Emergency Flagged',
                    $chartData['status']['labels'],
                    true
                )]);
                $this->assertSame(['Low', 'Medium', 'High', 'Critical'], $chartData['urgency']['labels']);
                $this->assertSame([0, 0, 1, 0], $chartData['urgency']['counts']);
                $this->assertSame(['Purok 4'], $chartData['purok']['labels']);
                $this->assertSame([2], $chartData['purok']['counts']);
                $this->assertSame(2, array_sum($chartData['weekly']['counts']));

                return true;
            });
    }
}
