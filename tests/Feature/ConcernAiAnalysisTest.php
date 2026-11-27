<?php

namespace Tests\Feature;

use App\Events\ReportSubmitted;
use App\Models\BarangayCase;
use App\Models\Category;
use App\Models\ComplaintIntake;
use App\Models\Report;
use App\Models\ReportAiResult;
use App\Models\StatusLog;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConcernAiAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    public function test_guest_can_analyze_concern_and_classify_community_problem_without_side_effects(): void
    {
        Event::fake([ReportSubmitted::class]);
        config(['services.gemini.key' => 'test-gemini-key']);

        $drainage = Category::query()->where('slug', 'drainage')->firstOrFail();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'classification' => 'community_problem',
                                'suggested_category' => 'drainage',
                                'summary' => 'A clogged drainage canal is overflowing near the road.',
                                'clarification_questions' => ['Which purok or landmark is closest to the clogged canal?'],
                                'is_emergency' => false,
                                'confidence' => 0.92,
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $response = $this->postJson(route('reports.analyze-concern'), [
            'description' => 'May baradong kanal dito at umaapaw na ang tubig sa kalsada.',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'classification' => 'community_problem',
                    'suggested_category' => [
                        'id' => $drainage->id,
                        'slug' => 'drainage',
                        'name' => $drainage->name,
                    ],
                    'summary' => 'A clogged drainage canal is overflowing near the road.',
                    'clarification_questions' => ['Which purok or landmark is closest to the clogged canal?'],
                    'is_emergency' => false,
                    'confidence' => 0.92,
                ],
            ]);

        $this->assertSame(0, Report::count());
        $this->assertSame(0, ReportAiResult::count());
        $this->assertSame(0, ComplaintIntake::count());
        $this->assertSame(0, BarangayCase::count());
        $this->assertSame(0, StatusLog::count());
        Event::assertNotDispatched(ReportSubmitted::class);
    }

    public function test_classifies_possible_formal_complaint_and_uncertain_with_expected_schema(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'text' => json_encode([
                                    'classification' => 'possible_formal_complaint',
                                    'suggested_category' => 'unclassified',
                                    'summary' => 'The resident describes an interpersonal dispute with a neighbor over unpaid debt.',
                                    'clarification_questions' => [],
                                    'is_emergency' => false,
                                    'confidence' => 0.88,
                                ]),
                            ]],
                        ],
                    ]],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'text' => json_encode([
                                    'classification' => 'uncertain',
                                    'suggested_category' => 'unclassified',
                                    'summary' => 'The description is too vague to identify the specific concern.',
                                    'clarification_questions' => ['What specific issue occurred?'],
                                    'is_emergency' => false,
                                    'confidence' => 0.75,
                                ]),
                            ]],
                        ],
                    ]],
                ], 200),
        ]);

        $this->postJson(route('reports.analyze-concern'), [
            'description' => 'My neighbor borrowed 5000 pesos last month and refuses to pay me back.',
        ])->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'classification' => 'possible_formal_complaint',
                    'suggested_category' => null,
                    'is_emergency' => false,
                ],
            ]);

        $this->postJson(route('reports.analyze-concern'), [
            'description' => 'Something weird happened outside earlier today.',
        ])->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'classification' => 'uncertain',
                    'suggested_category' => null,
                    'confidence' => 0.5,
                ],
            ]);
    }

    public function test_never_returns_inactive_or_nonexistent_category_id(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        Category::query()->where('slug', 'flooding')->update(['is_active' => false]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'classification' => 'incident',
                                'suggested_category' => 'flooding',
                                'summary' => 'Localized water accumulation reported.',
                                'clarification_questions' => [],
                                'is_emergency' => false,
                                'confidence' => 0.81,
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $this->postJson(route('reports.analyze-concern'), [
            'description' => 'Water is rising near the basketball court after the heavy rain.',
        ])->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'classification' => 'incident',
                    'suggested_category' => null,
                ],
            ]);
    }

    public function test_validation_rejects_invalid_inputs_and_does_not_call_gemini(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);
        Http::fake();

        Category::query()->where('slug', 'garbage')->update(['is_active' => false]);
        $inactiveCategory = Category::query()->where('slug', 'garbage')->firstOrFail();

        $this->postJson(route('reports.analyze-concern'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);

        $this->postJson(route('reports.analyze-concern'), ['description' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);

        $this->postJson(route('reports.analyze-concern'), ['description' => 'Too short'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);

        $this->postJson(route('reports.analyze-concern'), ['description' => str_repeat('a', 2001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);

        $this->postJson(route('reports.analyze-concern'), [
            'description' => 'This is a valid length concern description.',
            'category_id' => $inactiveCategory->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);

        Http::assertNothingSent();
    }

    public function test_graceful_failure_on_missing_key_timeout_provider_errors_and_invalid_ai_output(): void
    {
        config(['services.gemini.key' => null]);

        $this->postJson(route('reports.analyze-concern'), [
            'description' => 'Broken streetlight near the barangay hall entrance.',
        ])->assertStatus(503)
            ->assertJson([
                'success' => false,
                'available' => false,
            ]);

        config(['services.gemini.key' => 'test-gemini-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => fn () => throw new ConnectionException('Timed out'),
        ]);

        $this->postJson(route('reports.analyze-concern'), [
            'description' => 'Broken streetlight near the barangay hall entrance.',
        ])->assertStatus(503)
            ->assertJson([
                'success' => false,
                'available' => false,
                'message' => 'AI classification is temporarily unavailable. You can still submit your concern manually.',
            ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'Quota exceeded']], 429)
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'not-valid-json']]]]]], 200)
                ->push(['candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'classification' => 'invalid_type',
                    'suggested_category' => 'streetlight',
                    'summary' => 'Test summary.',
                    'clarification_questions' => [],
                    'is_emergency' => false,
                    'confidence' => 0.8,
                ])]]]]]], 200)
                ->push(['candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'classification' => 'community_problem',
                    'suggested_category' => 'streetlight',
                    'summary' => 'Test summary.',
                    'clarification_questions' => [],
                    'is_emergency' => false,
                    'confidence' => 1.5,
                ])]]]]]], 200),
        ]);

        for ($i = 0; $i < 4; $i++) {
            $this->postJson(route('reports.analyze-concern'), [
                'description' => 'Broken streetlight near the barangay hall entrance.',
            ])->assertStatus(503)
                ->assertJson([
                    'success' => false,
                    'available' => false,
                    'message' => 'AI classification is temporarily unavailable. You can still submit your concern manually.',
                ]);
        }

        $this->assertSame(0, Report::count());
        $this->assertSame(0, ReportAiResult::count());

        // Verify manual report submission remains completely unaffected after AI failures.
        $this->post(route('reports.store'), [
            'description' => 'Broken streetlight near the barangay hall entrance.',
            'landmark' => 'Barangay Hall Entrance',
            'consent' => '1',
            'has_consent_field' => '1',
        ])->assertRedirect();

        $this->assertSame(1, Report::count());
    }

    public function test_sends_only_description_and_optional_category_context_without_pii(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);
        $streetlight = Category::query()->where('slug', 'streetlight')->firstOrFail();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'classification' => 'community_problem',
                                'suggested_category' => 'streetlight',
                                'summary' => 'A streetlight is out along the road.',
                                'clarification_questions' => [],
                                'is_emergency' => false,
                                'confidence' => 0.9,
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $this->postJson(route('reports.analyze-concern'), [
            'description' => 'The streetlight post near the school has been flickering for three nights.',
            'category_id' => $streetlight->id,
            'reporter_name' => 'Juan Dela Cruz',
            'reporter_contact' => '09171234567',
            'reporter_email' => 'juan@example.com',
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            $body = json_encode($request->data());

            return str_contains($body, 'flickering for three nights')
                && str_contains($body, 'Streetlight')
                && ! str_contains($body, 'Juan Dela Cruz')
                && ! str_contains($body, '09171234567')
                && ! str_contains($body, 'juan@example.com');
        });
    }

    public function test_endpoint_is_rate_limited_to_ten_requests_per_minute(): void
    {
        config(['services.gemini.key' => null]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('reports.analyze-concern'), [
                'description' => 'Broken streetlight near the barangay hall entrance.',
            ])->assertStatus(503);
        }

        $this->postJson(route('reports.analyze-concern'), [
            'description' => 'Broken streetlight near the barangay hall entrance.',
        ])->assertStatus(429);
    }
}
