<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DuplicateSuggestion;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateReviewInterfaceTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Category $category;

    private Purok $purok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->staff()->create();
        $this->category = Category::query()->create([
            'name' => 'Drainage & Flooding',
            'slug' => 'drainage-and-flooding',
            'is_active' => true,
        ]);
        $this->purok = Purok::factory()->create(['name' => 'Purok 4']);
    }

    public function test_pending_suggestions_render_ranked_in_descending_score_order_with_safe_details(): void
    {
        $report = Report::factory()->create([
            'tracking_code' => 'BRG-MAIN001',
            'category_id' => $this->category->id,
            'purok_id' => $this->purok->id,
            'status' => 'submitted',
        ]);

        $lowerCandidate = Report::factory()->create([
            'tracking_code' => 'BRG-CAND-LOW',
            'category_id' => $this->category->id,
            'purok_id' => $this->purok->id,
            'landmark' => 'Near Old Chapel',
            'status' => 'under_review',
            'description' => 'Lower match drainage blockage near the old chapel.',
        ]);

        $higherCandidate = Report::factory()->create([
            'tracking_code' => 'BRG-CAND-HIGH',
            'category_id' => null,
            'resident_category_id' => $this->category->id,
            'purok_id' => null,
            'resident_purok_id' => $this->purok->id,
            'landmark' => 'Beside Market Gate',
            'status' => 'in_progress',
            'description' => 'Higher match overflowing canal beside the market gate.',
        ]);

        DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $lowerCandidate->id,
            'score' => 0.62,
            'decision' => 'pending',
        ]);

        DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $higherCandidate->id,
            'score' => 0.91,
            'decision' => 'pending',
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $report));

        $response->assertOk()
            ->assertSee('Possible Duplicate Reports')
            ->assertSee('2 pending')
            ->assertSee('91% similarity')
            ->assertSee('62% similarity')
            ->assertSeeInOrder(['BRG-CAND-HIGH', 'BRG-CAND-LOW'])
            ->assertSee('Drainage &amp; Flooding', false)
            ->assertSee('Purok 4')
            ->assertSee('Beside Market Gate')
            ->assertSee('Higher match overflowing canal beside the market gate.')
            ->assertSee('In Progress')
            ->assertSee($higherCandidate->created_at->format('M d, Y h:i A'));
    }

    public function test_non_pending_and_missing_or_merged_candidates_are_excluded_from_actionable_cards(): void
    {
        $report = Report::factory()->create([
            'tracking_code' => 'BRG-MAIN002',
            'category_id' => $this->category->id,
            'status' => 'submitted',
        ]);

        $dismissedCandidate = Report::factory()->create(['tracking_code' => 'BRG-DISM01', 'category_id' => $this->category->id]);
        $mergedDecisionCandidate = Report::factory()->create(['tracking_code' => 'BRG-MRGD01', 'category_id' => $this->category->id]);
        $unmergedCandidate = Report::factory()->create(['tracking_code' => 'BRG-UNMG01', 'category_id' => $this->category->id]);
        $alreadyMergedCandidate = Report::factory()->create([
            'tracking_code' => 'BRG-ALRDYMRG',
            'category_id' => $this->category->id,
            'status' => 'merged',
        ]);

        DuplicateSuggestion::create(['report_id' => $report->id, 'candidate_id' => $dismissedCandidate->id, 'score' => 0.80, 'decision' => 'dismissed']);
        DuplicateSuggestion::create(['report_id' => $report->id, 'candidate_id' => $mergedDecisionCandidate->id, 'score' => 0.81, 'decision' => 'merged']);
        DuplicateSuggestion::create(['report_id' => $report->id, 'candidate_id' => $unmergedCandidate->id, 'score' => 0.82, 'decision' => 'unmerged']);
        DuplicateSuggestion::create(['report_id' => $report->id, 'candidate_id' => $alreadyMergedCandidate->id, 'score' => 0.90, 'decision' => 'pending']);

        $response = $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $report));

        $response->assertOk()
            ->assertSee('0 pending')
            ->assertSee('No pending duplicate suggestions for')
            ->assertDontSee('80% similarity')
            ->assertDontSee('81% similarity')
            ->assertDontSee('82% similarity')
            ->assertDontSee('90% similarity')
            ->assertDontSee('BRG-ALRDYMRG');
    }

    public function test_empty_state_appears_and_show_page_is_strictly_read_only(): void
    {
        $report = Report::factory()->create([
            'tracking_code' => 'BRG-EMPTY01',
            'category_id' => $this->category->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Clogged drainage canal near highway.',
        ]);

        // Another matching report exists in DB, but show() must not auto-generate suggestions on GET
        Report::factory()->create([
            'category_id' => $this->category->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Clogged drainage canal near highway.',
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $report));

        $response->assertOk()
            ->assertSee('0 pending')
            ->assertSee('No pending duplicate suggestions for')
            ->assertSee('BRG-EMPTY01');

        $this->assertDatabaseCount('duplicate_suggestions', 0);
    }

    public function test_dismissal_and_merge_forms_use_correct_existing_routes_and_ids(): void
    {
        $report = Report::factory()->create([
            'tracking_code' => 'BRG-FORM01',
            'category_id' => $this->category->id,
            'status' => 'submitted',
        ]);
        $candidate = Report::factory()->create([
            'tracking_code' => 'BRG-CAND01',
            'category_id' => $this->category->id,
            'status' => 'under_review',
        ]);
        $suggestion = DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'score' => 0.87,
            'decision' => 'pending',
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $report));

        $dismissUrl = route('admin.reports.duplicate.dismiss', [$report, $suggestion]);
        $mergeUrl = route('admin.reports.duplicate', $report);

        $response->assertOk()
            ->assertSee('action="'.$dismissUrl.'"', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('action="'.$mergeUrl.'"', false)
            ->assertSee('data-duplicate-candidate-id="'.$candidate->id.'"', false)
            ->assertSee('value="'.$candidate->id.'"', false);
    }

    public function test_emergency_report_hides_merge_actions_and_emergency_candidates_are_excluded_from_merge_choices(): void
    {
        $emergencyReport = Report::factory()->create([
            'tracking_code' => 'BRG-EMG-MAIN',
            'category_id' => $this->category->id,
            'status' => 'submitted',
            'is_emergency' => true,
        ]);
        $normalCandidate = Report::factory()->create([
            'tracking_code' => 'BRG-NORM-C1',
            'category_id' => $this->category->id,
            'is_emergency' => false,
        ]);
        $suggestionOnEmergency = DuplicateSuggestion::create([
            'report_id' => $emergencyReport->id,
            'candidate_id' => $normalCandidate->id,
            'score' => 0.84,
            'decision' => 'pending',
        ]);

        $emgResponse = $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $emergencyReport));

        $emgResponse->assertOk()
            ->assertDontSee('id="duplicate-modal"', false)
            ->assertDontSee('Manual merge')
            ->assertDontSee('Merge into BRG-NORM-C1')
            ->assertSee('action="'.route('admin.reports.duplicate.dismiss', [$emergencyReport, $suggestionOnEmergency]).'"', false);

        // Normal report with an emergency candidate
        $normalReport = Report::factory()->create([
            'tracking_code' => 'BRG-NORM-MAIN',
            'category_id' => $this->category->id,
            'status' => 'submitted',
            'is_emergency' => false,
        ]);
        $emergencyCandidate = Report::factory()->create([
            'tracking_code' => 'BRG-EMG-CAND',
            'category_id' => $this->category->id,
            'is_emergency' => true,
            'status' => 'emergency_flagged',
        ]);
        DuplicateSuggestion::create([
            'report_id' => $normalReport->id,
            'candidate_id' => $emergencyCandidate->id,
            'score' => 0.79,
            'decision' => 'pending',
        ]);

        $normResponse = $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $normalReport));

        $normResponse->assertOk()
            ->assertSee('BRG-EMG-CAND')
            ->assertSee('Emergency candidate cannot be selected for merge')
            ->assertDontSee('Merge into BRG-EMG-CAND');

        $this->assertSame(
            1,
            preg_match('/<select id="duplicate-candidate"[^>]*>(.*?)<\/select>/s', $normResponse->getContent(), $selectMatches)
        );
        $this->assertStringNotContainsString('value="'.$emergencyCandidate->id.'"', $selectMatches[1]);
        $this->assertStringNotContainsString('BRG-EMG-CAND', $selectMatches[1]);
    }

    public function test_candidate_personal_details_are_not_rendered_on_candidate_cards(): void
    {
        $report = Report::factory()->create([
            'tracking_code' => 'BRG-PRIV-MAIN',
            'category_id' => $this->category->id,
            'reporter_name' => 'Main Reporter Visible Elsewhere',
        ]);

        $candidate = Report::factory()->create([
            'tracking_code' => 'BRG-PRIV-CAND',
            'category_id' => $this->category->id,
            'reporter_name' => 'UNIQUE_SECRET_CANDIDATE_NAME_991',
            'reporter_contact' => '09998887766',
            'reporter_email' => 'unique-secret-candidate-991@example.test',
        ]);

        DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'score' => 0.88,
            'decision' => 'pending',
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $report));

        $response->assertOk()
            ->assertSee('BRG-PRIV-CAND')
            ->assertDontSee('UNIQUE_SECRET_CANDIDATE_NAME_991')
            ->assertDontSee('09998887766')
            ->assertDontSee('unique-secret-candidate-991@example.test');
    }
}
