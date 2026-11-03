<?php

namespace Tests\Feature;

use App\Models\DuplicateSuggestion;
use App\Models\Office;
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

    public function test_dashboard_shows_tabs_metrics_and_items_needing_attention(): void
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

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('Analytics')
            ->assertSee('Needs Attention')
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
}
