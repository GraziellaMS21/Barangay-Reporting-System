<?php

namespace Tests\Feature;

use App\Events\ReportSubmitted;
use App\Listeners\Engine\ProcessNewReport;
use App\Listeners\Notifications\SendReportReceived;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use App\Services\GeminiService;
use Database\Seeders\CategoryQuestionSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PurokSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AsyncReportScreeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PurokSeeder::class, CategorySeeder::class, CategoryQuestionSeeder::class]);
    }

    public function test_listener_implements_should_queue_and_configures_queue_properties(): void
    {
        $listener = new ProcessNewReport;

        $this->assertInstanceOf(ShouldQueue::class, $listener);
        $this->assertTrue($listener->afterCommit);
        $this->assertTrue($listener->deleteWhenMissingModels);
        $this->assertSame(2, $listener->tries);
        $this->assertSame([10], $listener->backoff);
    }

    public function test_submitting_report_queues_process_new_report_listener(): void
    {
        Queue::fake();

        $category = Category::where('slug', 'drainage')->firstOrFail();
        $purok = Purok::firstOrFail();

        $response = $this->post(route('reports.store'), [
            'report_type' => 'non_emergency',
            'category_id' => $category->id,
            'description' => 'The canal is overflowing and needs immediate cleaning.',
            'purok_id' => $purok->id,
            'consent' => '1',
            'has_consent_field' => '1',
        ]);

        $report = Report::latest()->firstOrFail();

        $response->assertRedirect(route('reports.success', $report->tracking_code));

        Queue::assertPushed(CallQueuedListener::class, function ($job) use ($report) {
            return $job->class === ProcessNewReport::class
                && $job->data[0] instanceof ReportSubmitted
                && $job->data[0]->report->id === $report->id;
        });
    }

    public function test_report_submitted_queues_process_new_report_and_send_report_received_once_each(): void
    {
        Queue::fake();

        $report = Report::factory()->create();
        ReportSubmitted::dispatch($report);

        $pushed = Queue::pushed(CallQueuedListener::class);

        $processNewReportCount = $pushed->filter(
            fn (CallQueuedListener $job): bool => $job->class === ProcessNewReport::class
        )->count();

        $sendReportReceivedCount = $pushed->filter(
            fn (CallQueuedListener $job): bool => $job->class === SendReportReceived::class
        )->count();

        $this->assertSame(1, $processNewReportCount);
        $this->assertSame(1, $sendReportReceivedCount);
    }

    public function test_resident_submission_does_not_make_synchronous_gemini_api_call(): void
    {
        Http::fake();

        $category = Category::where('slug', 'drainage')->firstOrFail();
        $purok = Purok::firstOrFail();

        // With Queue::fake, the queued listener is not executed synchronously
        Queue::fake();

        $response = $this->post(route('reports.store'), [
            'report_type' => 'non_emergency',
            'category_id' => $category->id,
            'description' => 'A large crack formed in the street after heavy rainfall.',
            'purok_id' => $purok->id,
            'consent' => '1',
            'has_consent_field' => '1',
        ]);

        $response->assertRedirect();
        Http::assertNothingSent();
    }

    public function test_queued_listener_successfully_processes_report_and_persists_ai_results(): void
    {
        config(['services.gemini.key' => 'test-api-key']);

        $category = Category::where('slug', 'drainage')->firstOrFail();
        $report = Report::factory()->create([
            'category_id' => $category->id,
            'description' => 'Heavy trash clog in the drainage canal.',
            'ai_status' => 'pending',
            'status' => 'submitted',
        ]);

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
                                        'ai_description' => 'Drainage canal clogged with trash causing water backflow.',
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

        $listener = new ProcessNewReport;
        $listener->handle(new ReportSubmitted($report));

        $report->refresh();
        $this->assertSame('done', $report->ai_status);
        $this->assertSame('submitted', $report->status); // Canonical status untouched

        $this->assertDatabaseHas('report_ai_results', [
            'report_id' => $report->id,
            'suggested_category' => 'drainage',
            'suggested_urgency' => 'High',
            'summary' => 'Drainage canal clogged with trash causing water backflow.',
            'emergency_flag' => false,
            'error' => null,
        ]);
    }

    public function test_transient_failure_rethrown_when_attempts_remain(): void
    {
        config(['services.gemini.key' => 'test-api-key']);

        $report = Report::factory()->create([
            'description' => 'Streetlight not turning on.',
            'ai_status' => 'pending',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function () {
                throw new ConnectionException('Connection timed out after 3000ms');
            },
        ]);

        $listener = new ProcessNewReport;
        $listener->setJob(new SyncJob(app(), '', 'database', 'database'));

        $this->expectException(ConnectionException::class);

        $listener->handle(new ReportSubmitted($report));
    }

    public function test_transient_failure_records_failed_state_on_final_attempt(): void
    {
        config(['services.gemini.key' => 'test-api-key']);

        $report = Report::factory()->create([
            'description' => 'Streetlight not turning on.',
            'ai_status' => 'pending',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $listener = new class extends ProcessNewReport
        {
            public function attempts(): int
            {
                return 2; // Simulating final attempt
            }
        };

        $listener->handle(new ReportSubmitted($report));

        $report->refresh();
        $this->assertSame('failed', $report->ai_status);
        $this->assertDatabaseHas('report_ai_results', [
            'report_id' => $report->id,
            'error' => 'AI unavailable, needs manual review.',
        ]);
    }

    public function test_permanent_failure_does_not_retry_and_marks_failed(): void
    {
        // No API key configured -> permanent error
        config(['services.gemini.key' => null]);

        $report = Report::factory()->create([
            'description' => 'Pothole on the main street.',
            'ai_status' => 'pending',
        ]);

        $listener = new ProcessNewReport;
        $listener->setJob(new SyncJob(app(), '', 'default', 'sync'));

        // Should not throw exception, marks failed directly
        $listener->handle(new ReportSubmitted($report));

        $report->refresh();
        $this->assertSame('failed', $report->ai_status);
        $this->assertDatabaseHas('report_ai_results', [
            'report_id' => $report->id,
            'error' => 'AI unavailable, needs manual review.',
        ]);
    }

    public function test_staff_review_before_job_execution_aborts_ai_analysis(): void
    {
        config(['services.gemini.key' => 'test-api-key']);
        Http::fake();

        $staff = User::factory()->create(['role' => 'staff']);
        $staff->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
        ]);
        $report = Report::factory()->create([
            'description' => 'Some report description.',
            'ai_status' => 'pending',
            'reviewed_at' => now(),
            'reviewed_by' => $staff->id,
        ]);

        $listener = new ProcessNewReport;
        $listener->handle(new ReportSubmitted($report));

        Http::assertNothingSent();
        $report->refresh();
        $this->assertSame('pending', $report->ai_status);
        $this->assertDatabaseMissing('report_ai_results', ['report_id' => $report->id]);
    }

    public function test_staff_review_during_analysis_prevents_stale_ai_overwrite(): void
    {
        config(['services.gemini.key' => 'test-api-key']);

        $category = Category::where('slug', 'drainage')->firstOrFail();
        $report = Report::factory()->create([
            'category_id' => $category->id,
            'description' => 'Garbage blocking the culvert.',
            'ai_status' => 'processing',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'category' => 'drainage',
                                        'urgency' => 'Medium',
                                        'ai_description' => 'Culvert clogged with garbage.',
                                        'photo_mismatch' => false,
                                        'is_emergency' => false,
                                        'confidence' => 0.88,
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Simulate staff reviewing the report right while Gemini was processing
        $report->update([
            'reviewed_at' => now(),
            'reviewed_by' => User::factory()->create(['role' => 'staff'])->id,
            'urgency' => 'Critical', // Staff set Critical
        ]);

        $gemini = app(GeminiService::class);
        $gemini->analyze($report);

        $report->refresh();
        $this->assertSame('Critical', $report->urgency); // Preserved
        $this->assertDatabaseMissing('report_ai_results', ['report_id' => $report->id]);
    }

    public function test_deleted_report_is_safely_ignored_by_listener(): void
    {
        $report = Report::factory()->create([
            'description' => 'Report about to be deleted.',
        ]);

        $event = new ReportSubmitted($report);
        $report->delete(); // Report no longer in database

        $listener = new ProcessNewReport;
        $listener->handle($event);

        $this->assertTrue(true); // Handled cleanly without exception
    }

    public function test_duplicate_execution_is_idempotent_and_reuses_cached_result(): void
    {
        config(['services.gemini.key' => 'test-api-key']);

        $report = Report::factory()->create([
            'description' => 'Report that will be processed twice.',
            'ai_status' => 'pending',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => json_encode([
                                            'category' => 'other',
                                            'urgency' => 'Low',
                                            'ai_description' => 'Minor concern.',
                                            'photo_mismatch' => false,
                                            'is_emergency' => false,
                                            'confidence' => 0.70,
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200)
                ->push(['error' => 'Should not be called second time'], 500),
        ]);

        $listener = new ProcessNewReport;
        $listener->handle(new ReportSubmitted($report));

        $this->assertSame('done', $report->fresh()->ai_status);
        $this->assertSame(1, $report->fresh()->aiResult()->count());

        // Second execution should hit the cache check and not make an HTTP request
        $listener->handle(new ReportSubmitted($report));

        Http::assertSentCount(1);
        $this->assertSame('done', $report->fresh()->ai_status);
        $this->assertSame(1, $report->fresh()->aiResult()->count());
    }

    public function test_manual_retry_endpoint_remains_synchronous(): void
    {
        config(['services.gemini.key' => 'test-api-key']);

        $staff = User::factory()->create(['role' => 'staff']);
        $staff->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
        ]);
        $report = Report::factory()->create([
            'description' => 'Previously failed AI screening report.',
            'ai_status' => 'failed',
        ]);

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
                                        'ai_description' => 'Successfully retried AI analysis.',
                                        'photo_mismatch' => false,
                                        'is_emergency' => false,
                                        'confidence' => 0.92,
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($staff)
            ->post(route('admin.reports.retry-ai', $report));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'AI analysis completed.');

        $report->refresh();
        $this->assertSame('done', $report->ai_status);
        $this->assertDatabaseHas('report_ai_results', [
            'report_id' => $report->id,
            'summary' => 'Successfully retried AI analysis.',
            'error' => null,
        ]);
    }
}
