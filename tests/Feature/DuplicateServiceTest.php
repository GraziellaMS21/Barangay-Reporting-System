<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DuplicateSuggestion;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use App\Services\DuplicateService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateServiceTest extends TestCase
{
    use RefreshDatabase;

    private DuplicateService $service;

    private Category $drainageCategory;

    private Category $lightingCategory;

    private Purok $purok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DuplicateService::class);
        $this->drainageCategory = Category::query()->create([
            'name' => 'Drainage & Flooding',
            'slug' => 'drainage-and-flooding',
            'is_active' => true,
        ]);
        $this->lightingCategory = Category::query()->create([
            'name' => 'Street Lighting',
            'slug' => 'street-lighting',
            'is_active' => true,
        ]);
        $this->purok = Purok::factory()->create(['name' => 'Purok 1']);
    }

    public function test_find_matches_includes_candidate_within_150_meters_and_excludes_beyond_150_meters(): void
    {
        $baseTime = now();

        // ~111m north of target (0.001 deg lat ≈ 111.19m)
        $withinRange = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'purok_id' => $this->purok->id,
            'lat' => 6.9110,
            'lng' => 122.0700,
            'description' => 'Clogged drainage canal overflowing onto the main street near chapel',
            'created_at' => $baseTime->copy()->subHours(2),
        ]);

        // ~200m north of target (0.0018 deg lat ≈ 200.15m)
        $outsideRange = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'purok_id' => $this->purok->id,
            'lat' => 6.9118,
            'lng' => 122.0700,
            'description' => 'Clogged drainage canal overflowing onto the main street near chapel',
            'created_at' => $baseTime->copy()->subHours(1),
        ]);

        $target = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'purok_id' => $this->purok->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Clogged drainage canal overflowing onto the main street near chapel',
            'created_at' => $baseTime,
        ]);

        $matches = $this->service->findMatches($target);

        $this->assertCount(1, $matches);
        $this->assertSame($withinRange->id, $matches->first()['candidate']->id);
        $this->assertSame('geo', $matches->first()['match_type']);
        $this->assertNotNull($matches->first()['distance_meters']);
        $this->assertLessThanOrEqual(150.0, $matches->first()['distance_meters']);
        $this->assertNotContains($outsideRange->id, $matches->pluck('candidate.id')->all());
    }

    public function test_find_matches_enforces_45_percent_similarity_threshold(): void
    {
        $baseTime = now();

        $similar = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Flooded drainage canal blocking pedestrian walkway near the public market',
            'created_at' => $baseTime->copy()->subHours(3),
        ]);

        $dissimilar = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Broken concrete culvert pipe under bridge',
            'created_at' => $baseTime->copy()->subHours(2),
        ]);

        $target = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Flooded drainage canal blocking pedestrian walkway near the market',
            'created_at' => $baseTime,
        ]);

        $matches = $this->service->findMatches($target);

        $this->assertCount(1, $matches);
        $this->assertSame($similar->id, $matches->first()['candidate']->id);
        $this->assertGreaterThanOrEqual(45.0, $matches->first()['similarity_score']);
        $this->assertNotContains($dissimilar->id, $matches->pluck('candidate.id')->all());
    }

    public function test_find_matches_uses_effective_category_and_excludes_mismatched_categories(): void
    {
        $baseTime = now();

        // Candidate has null official category_id, but matching resident_category_id
        $residentCategoryCandidate = Report::factory()->create([
            'category_id' => null,
            'resident_category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Severe flooding and clogged drainage along highway intersection',
            'created_at' => $baseTime->copy()->subHours(2),
        ]);

        // Candidate was reclassified to lightingCategory even though resident_category_id was drainageCategory
        $reclassifiedDifferentCategory = Report::factory()->create([
            'category_id' => $this->lightingCategory->id,
            'resident_category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Severe flooding and clogged drainage along highway intersection',
            'created_at' => $baseTime->copy()->subHours(1),
        ]);

        // Target has null official category_id, using resident_category_id as effective category
        $target = Report::factory()->create([
            'category_id' => null,
            'resident_category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Severe flooding and clogged drainage along highway intersection',
            'created_at' => $baseTime,
        ]);

        $matches = $this->service->findMatches($target);

        $this->assertCount(1, $matches);
        $this->assertSame($residentCategoryCandidate->id, $matches->first()['candidate']->id);
        $this->assertNotContains($reclassifiedDifferentCategory->id, $matches->pluck('candidate.id')->all());
    }

    public function test_find_matches_excludes_self_and_merged_reports(): void
    {
        $baseTime = now();

        Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'status' => 'merged',
            'description' => 'Clogged drainage canal overflowing near barangay hall',
            'created_at' => $baseTime->copy()->subHours(2),
        ]);

        $activeCandidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'status' => 'submitted',
            'description' => 'Clogged drainage canal overflowing near barangay hall',
            'created_at' => $baseTime->copy()->subHours(1),
        ]);

        $target = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'status' => 'submitted',
            'description' => 'Clogged drainage canal overflowing near barangay hall',
            'created_at' => $baseTime,
        ]);

        $matches = $this->service->findMatches($target);

        $matchedIds = $matches->pluck('candidate.id')->all();
        $this->assertSame([$activeCandidate->id], $matchedIds);
        $this->assertNotContains($target->id, $matchedIds);
    }

    public function test_find_matches_respects_14_day_window_and_excludes_reports_created_after_target(): void
    {
        $targetTime = now()->subDays(2);

        $tooOld = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage ditch beside elementary school',
            'created_at' => $targetTime->copy()->subDays(14)->subMinute(),
        ]);

        $withinWindow = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage ditch beside elementary school',
            'created_at' => $targetTime->copy()->subDays(13),
        ]);

        $createdAfterTarget = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage ditch beside elementary school',
            'created_at' => $targetTime->copy()->addMinute(),
        ]);

        $target = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage ditch beside elementary school',
            'created_at' => $targetTime,
        ]);

        $matches = $this->service->findMatches($target);

        $matchedIds = $matches->pluck('candidate.id')->all();
        $this->assertSame([$withinWindow->id], $matchedIds);
        $this->assertNotContains($tooOld->id, $matchedIds);
        $this->assertNotContains($createdAfterTarget->id, $matchedIds);
    }

    public function test_find_matches_uses_same_purok_fallback_with_null_distance_when_coordinates_missing(): void
    {
        $baseTime = now();
        $otherPurok = Purok::factory()->create(['name' => 'Purok 2']);

        $samePurokCandidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'purok_id' => $this->purok->id,
            'lat' => null,
            'lng' => null,
            'description' => 'Blocked stormwater drain flooding the interior alley',
            'created_at' => $baseTime->copy()->subHours(2),
        ]);

        $otherPurokCandidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'purok_id' => $otherPurok->id,
            'lat' => null,
            'lng' => null,
            'description' => 'Blocked stormwater drain flooding the interior alley',
            'created_at' => $baseTime->copy()->subHours(1),
        ]);

        $target = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'purok_id' => $this->purok->id,
            'lat' => null,
            'lng' => null,
            'description' => 'Blocked stormwater drain flooding the interior alley',
            'created_at' => $baseTime,
        ]);

        $matches = $this->service->findMatches($target);

        $this->assertCount(1, $matches);
        $this->assertSame($samePurokCandidate->id, $matches->first()['candidate']->id);
        $this->assertNull($matches->first()['distance_meters']);
        $this->assertSame('purok', $matches->first()['match_type']);
        $this->assertNotContains($otherPurokCandidate->id, $matches->pluck('candidate.id')->all());
    }

    public function test_find_matches_returns_empty_when_neither_coordinates_nor_purok_exist(): void
    {
        $baseTime = now();

        Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'purok_id' => $this->purok->id,
            'lat' => null,
            'lng' => null,
            'description' => 'Drainage canal overflowing in the neighborhood',
            'created_at' => $baseTime->copy()->subHour(),
        ]);

        $target = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'purok_id' => null,
            'lat' => null,
            'lng' => null,
            'description' => 'Drainage canal overflowing in the neighborhood',
            'created_at' => $baseTime,
        ]);

        $this->assertTrue($this->service->findMatches($target)->isEmpty());
    }

    public function test_find_matches_returns_empty_when_no_effective_category_exists(): void
    {
        $baseTime = now();

        Report::factory()->create([
            'category_id' => null,
            'resident_category_id' => null,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Drainage canal overflowing in the neighborhood',
            'created_at' => $baseTime->copy()->subHour(),
        ]);

        $target = Report::factory()->create([
            'category_id' => null,
            'resident_category_id' => null,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Drainage canal overflowing in the neighborhood',
            'created_at' => $baseTime,
        ]);

        $this->assertTrue($this->service->findMatches($target)->isEmpty());
    }

    public function test_find_matches_is_strictly_read_only_and_creates_zero_database_records(): void
    {
        $baseTime = now();

        Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage canal blocking road access',
            'created_at' => $baseTime->copy()->subHour(),
        ]);

        $target = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage canal blocking road access',
            'created_at' => $baseTime,
        ]);

        $matches = $this->service->findMatches($target);

        $this->assertCount(1, $matches);
        $this->assertDatabaseCount('duplicate_suggestions', 0);
    }

    public function test_record_suggestions_persists_pending_suggestions_with_normalized_scores_and_is_idempotent(): void
    {
        $baseTime = now();

        $candidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage canal blocking road access',
            'created_at' => $baseTime->copy()->subHour(),
        ]);

        $target = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage canal blocking road access',
            'created_at' => $baseTime,
        ]);

        $firstPass = $this->service->recordSuggestions($target);
        $secondPass = $this->service->recordSuggestions($target);

        $this->assertCount(1, $firstPass);
        $this->assertCount(1, $secondPass);
        $this->assertDatabaseCount('duplicate_suggestions', 1);

        $suggestion = DuplicateSuggestion::query()->firstOrFail();
        $this->assertSame($target->id, $suggestion->report_id);
        $this->assertSame($candidate->id, $suggestion->candidate_id);
        $this->assertSame('pending', $suggestion->decision);
        $this->assertGreaterThanOrEqual(0.45, $suggestion->score);
        $this->assertLessThanOrEqual(1.0, $suggestion->score);
    }

    public function test_record_suggestions_does_not_reset_dismissed_merged_or_unmerged_decisions(): void
    {
        $staff = User::factory()->staff()->create();
        $baseTime = now();

        $dismissedCandidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage canal blocking road access',
            'created_at' => $baseTime->copy()->subHours(3),
        ]);

        $mergedCandidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage canal blocking road access',
            'created_at' => $baseTime->copy()->subHours(2),
        ]);

        $unmergedCandidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage canal blocking road access',
            'created_at' => $baseTime->copy()->subHour(),
        ]);

        $target = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Overflowing drainage canal blocking road access',
            'created_at' => $baseTime,
        ]);

        DuplicateSuggestion::create([
            'report_id' => $target->id,
            'candidate_id' => $dismissedCandidate->id,
            'score' => 0.80,
            'decision' => 'dismissed',
            'decided_by' => $staff->id,
        ]);

        DuplicateSuggestion::create([
            'report_id' => $target->id,
            'candidate_id' => $mergedCandidate->id,
            'score' => 0.85,
            'decision' => 'merged',
            'decided_by' => $staff->id,
        ]);

        DuplicateSuggestion::create([
            'report_id' => $target->id,
            'candidate_id' => $unmergedCandidate->id,
            'score' => 0.75,
            'decision' => 'unmerged',
            'decided_by' => $staff->id,
        ]);

        $this->service->recordSuggestions($target);

        $this->assertDatabaseHas('duplicate_suggestions', [
            'report_id' => $target->id,
            'candidate_id' => $dismissedCandidate->id,
            'decision' => 'dismissed',
            'decided_by' => $staff->id,
        ]);
        $this->assertDatabaseHas('duplicate_suggestions', [
            'report_id' => $target->id,
            'candidate_id' => $mergedCandidate->id,
            'decision' => 'merged',
            'decided_by' => $staff->id,
        ]);
        $this->assertDatabaseHas('duplicate_suggestions', [
            'report_id' => $target->id,
            'candidate_id' => $unmergedCandidate->id,
            'decision' => 'unmerged',
            'decided_by' => $staff->id,
        ]);
    }

    public function test_unique_index_rejects_duplicate_report_candidate_pairs(): void
    {
        $report = Report::factory()->create();
        $candidate = Report::factory()->create();

        DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'score' => 0.90,
            'decision' => 'pending',
        ]);

        $this->expectException(QueryException::class);

        DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'score' => 0.95,
            'decision' => 'pending',
        ]);
    }
}
