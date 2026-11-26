<?php

namespace Tests\Feature;

use App\Events\ReportSubmitted;
use App\Listeners\Engine\ProcessNewReport;
use App\Models\Category;
use App\Models\DuplicateSuggestion;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use App\Services\DuplicateService;
use Database\Seeders\CategoryQuestionSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PurokSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AsyncDuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    private Category $drainageCategory;

    private Purok $purok;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PurokSeeder::class, CategorySeeder::class, CategoryQuestionSeeder::class]);

        $this->drainageCategory = Category::where('slug', 'drainage')->firstOrFail();
        $this->purok = Purok::firstOrFail();
    }

    public function test_duplicate_suggestions_are_recorded_after_successful_ai_screening(): void
    {
        config(['services.gemini.key' => 'test-api-key']);
        $baseTime = now();

        $candidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'purok_id' => $this->purok->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Heavy trash clog in the drainage canal near the chapel.',
            'created_at' => $baseTime->copy()->subHours(2),
        ]);

        $report = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'resident_category_id' => $this->drainageCategory->id,
            'purok_id' => $this->purok->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Heavy trash clog in the drainage canal near the chapel.',
            'ai_status' => 'pending',
            'status' => 'submitted',
            'created_at' => $baseTime,
        ]);

        $this->fakeGeminiSuccess();

        (new ProcessNewReport)->handle(new ReportSubmitted($report));

        $report->refresh();
        $this->assertSame('done', $report->ai_status);
        $this->assertSame('submitted', $report->status);
        $this->assertNull($report->merged_into_id);

        $this->assertDatabaseHas('duplicate_suggestions', [
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'decision' => 'pending',
        ]);
    }

    public function test_duplicate_suggestions_are_recorded_after_permanent_ai_failure(): void
    {
        config(['services.gemini.key' => null]);
        $baseTime = now();

        $candidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'purok_id' => $this->purok->id,
            'lat' => null,
            'lng' => null,
            'description' => 'Overflowing drainage canal beside the basketball court.',
            'created_at' => $baseTime->copy()->subHour(),
        ]);

        $report = Report::factory()->create([
            'category_id' => null,
            'resident_category_id' => $this->drainageCategory->id,
            'purok_id' => $this->purok->id,
            'lat' => null,
            'lng' => null,
            'description' => 'Overflowing drainage canal beside the basketball court.',
            'ai_status' => 'pending',
            'status' => 'submitted',
            'created_at' => $baseTime,
        ]);

        (new ProcessNewReport)->handle(new ReportSubmitted($report));

        $report->refresh();
        $this->assertSame('failed', $report->ai_status);
        $this->assertSame('submitted', $report->status);
        $this->assertNull($report->merged_into_id);

        $this->assertDatabaseHas('duplicate_suggestions', [
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'decision' => 'pending',
        ]);
    }

    public function test_insufficient_matching_information_produces_no_suggestions_without_breaking_ai_screening(): void
    {
        config(['services.gemini.key' => 'test-api-key']);
        $baseTime = now();

        Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'purok_id' => $this->purok->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Drainage canal overflowing on main road.',
            'created_at' => $baseTime->copy()->subHour(),
        ]);

        // No effective category and no coordinates/purok
        $report = Report::factory()->create([
            'category_id' => null,
            'resident_category_id' => null,
            'purok_id' => null,
            'lat' => null,
            'lng' => null,
            'description' => 'Drainage canal overflowing on main road.',
            'ai_status' => 'pending',
            'status' => 'submitted',
            'created_at' => $baseTime,
        ]);

        $this->fakeGeminiSuccess();

        (new ProcessNewReport)->handle(new ReportSubmitted($report));

        $report->refresh();
        $this->assertSame('done', $report->ai_status);
        $this->assertDatabaseCount('duplicate_suggestions', 0);
    }

    public function test_transient_ai_failure_does_not_run_duplicate_detection_prematurely_and_runs_on_successful_retry(): void
    {
        config(['services.gemini.key' => 'test-api-key']);
        $baseTime = now();

        $candidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Blocked drainage pipe causing stagnant water.',
            'created_at' => $baseTime->copy()->subHour(),
        ]);

        $report = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Blocked drainage pipe causing stagnant water.',
            'ai_status' => 'pending',
            'status' => 'submitted',
            'created_at' => $baseTime,
        ]);

        // Attempt 1: transient connection error
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function () {
                throw new ConnectionException('Temporary timeout');
            },
        ]);

        $attemptOne = new ProcessNewReport;
        $attemptOne->setJob(new SyncJob(app(), '', 'database', 'database'));

        try {
            $attemptOne->handle(new ReportSubmitted($report));
            $this->fail('Expected ConnectionException to be rethrown on attempt 1.');
        } catch (ConnectionException) {
            // Expected rethrow for queue retry
        }

        $this->assertDatabaseCount('duplicate_suggestions', 0);

        // Attempt 2: succeeds
        Http::swap(new Factory);
        $this->fakeGeminiSuccess();

        $attemptTwo = new class extends ProcessNewReport
        {
            public function attempts(): int
            {
                return 2;
            }
        };
        $attemptTwo->setJob(new SyncJob(app(), '', 'database', 'database'));
        $attemptTwo->handle(new ReportSubmitted($report));

        $report->refresh();
        $this->assertSame('done', $report->ai_status);
        $this->assertDatabaseHas('duplicate_suggestions', [
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'decision' => 'pending',
        ]);
    }

    public function test_manually_reviewed_non_merged_report_runs_duplicate_detection_without_altering_staff_decisions(): void
    {
        config(['services.gemini.key' => 'test-api-key']);
        Http::fake();

        $staff = User::factory()->staff()->create();
        $baseTime = now();

        $candidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Clogged culvert near the barangay health center.',
            'created_at' => $baseTime->copy()->subHour(),
        ]);

        $report = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Clogged culvert near the barangay health center.',
            'ai_status' => 'pending',
            'status' => 'under_review',
            'reviewed_by' => $staff->id,
            'reviewed_at' => $baseTime->copy()->subMinutes(5),
            'created_at' => $baseTime,
        ]);

        (new ProcessNewReport)->handle(new ReportSubmitted($report));

        Http::assertNothingSent();
        $report->refresh();
        $this->assertSame('pending', $report->ai_status);
        $this->assertSame('under_review', $report->status);
        $this->assertSame($this->drainageCategory->id, $report->category_id);

        $this->assertDatabaseHas('duplicate_suggestions', [
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'decision' => 'pending',
        ]);
    }

    public function test_merged_reports_are_skipped_for_duplicate_detection(): void
    {
        config(['services.gemini.key' => null]);
        $baseTime = now();

        $parent = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Collapsed drainage cover on the sidewalk.',
            'created_at' => $baseTime->copy()->subHour(),
        ]);

        $mergedReport = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Collapsed drainage cover on the sidewalk.',
            'status' => 'merged',
            'merged_into_id' => $parent->id,
            'ai_status' => 'pending',
            'created_at' => $baseTime,
        ]);

        (new ProcessNewReport)->handle(new ReportSubmitted($mergedReport));

        $this->assertDatabaseCount('duplicate_suggestions', 0);
        $this->assertSame('merged', $mergedReport->fresh()->status);
    }

    public function test_repeated_job_execution_is_idempotent_and_preserves_existing_decisions(): void
    {
        config(['services.gemini.key' => null]);
        $staff = User::factory()->staff()->create();
        $baseTime = now();

        $pendingCandidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Flooded drainage canal along the school perimeter.',
            'created_at' => $baseTime->copy()->subHours(3),
        ]);

        $dismissedCandidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Flooded drainage canal along the school perimeter.',
            'created_at' => $baseTime->copy()->subHours(2),
        ]);

        $mergedCandidate = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Flooded drainage canal along the school perimeter.',
            'created_at' => $baseTime->copy()->subHour(),
        ]);

        $report = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Flooded drainage canal along the school perimeter.',
            'ai_status' => 'pending',
            'status' => 'submitted',
            'created_at' => $baseTime,
        ]);

        DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $dismissedCandidate->id,
            'score' => 0.85,
            'decision' => 'dismissed',
            'decided_by' => $staff->id,
        ]);

        DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $mergedCandidate->id,
            'score' => 0.90,
            'decision' => 'merged',
            'decided_by' => $staff->id,
        ]);

        $listener = new ProcessNewReport;
        $listener->handle(new ReportSubmitted($report));
        $listener->handle(new ReportSubmitted($report));

        $this->assertDatabaseCount('duplicate_suggestions', 3);
        $this->assertDatabaseHas('duplicate_suggestions', [
            'report_id' => $report->id,
            'candidate_id' => $pendingCandidate->id,
            'decision' => 'pending',
        ]);
        $this->assertDatabaseHas('duplicate_suggestions', [
            'report_id' => $report->id,
            'candidate_id' => $dismissedCandidate->id,
            'decision' => 'dismissed',
            'decided_by' => $staff->id,
        ]);
        $this->assertDatabaseHas('duplicate_suggestions', [
            'report_id' => $report->id,
            'candidate_id' => $mergedCandidate->id,
            'decision' => 'merged',
            'decided_by' => $staff->id,
        ]);

        $report->refresh();
        $this->assertSame('submitted', $report->status);
        $this->assertNull($report->merged_into_id);
    }

    public function test_duplicate_detection_failure_is_logged_without_corrupting_ai_status_or_rethrowing(): void
    {
        config(['services.gemini.key' => 'test-api-key']);
        $this->fakeGeminiSuccess();

        $report = Report::factory()->create([
            'category_id' => $this->drainageCategory->id,
            'lat' => 6.9100,
            'lng' => 122.0700,
            'description' => 'Drainage canal overflowing.',
            'ai_status' => 'pending',
            'status' => 'submitted',
        ]);

        $mockDuplicateService = Mockery::mock(DuplicateService::class);
        $mockDuplicateService->shouldReceive('recordSuggestions')
            ->once()
            ->andThrow(new RuntimeException('Simulated duplicate service failure'));
        $this->app->instance(DuplicateService::class, $mockDuplicateService);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($report): bool {
                return str_contains($message, 'Duplicate detection failed')
                    && $context['report_id'] === $report->id
                    && $context['exception'] === RuntimeException::class
                    && $context['message'] === 'Simulated duplicate service failure';
            });

        (new ProcessNewReport)->handle(new ReportSubmitted($report));

        $report->refresh();
        $this->assertSame('done', $report->ai_status);
        $this->assertSame('submitted', $report->status);
        $this->assertNull($report->merged_into_id);
    }

    private function fakeGeminiSuccess(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'category' => 'drainage',
                                        'urgency' => 'High',
                                        'ai_description' => 'Drainage canal clogged with trash.',
                                        'photo_mismatch' => false,
                                        'is_emergency' => false,
                                        'confidence' => 0.95,
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
                'modelVersion' => 'gemini-2.5-flash',
            ], 200),
        ]);
    }
}
