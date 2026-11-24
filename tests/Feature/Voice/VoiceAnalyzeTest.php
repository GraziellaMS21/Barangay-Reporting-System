<?php

namespace Tests\Feature\Voice;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportAudio;
use App\Models\Setting;
use App\Models\User;
use App\Services\Voice\VoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceAnalyzeTest extends TestCase
{
    use RefreshDatabase;

    private User $authorizedStaff;

    private User $unauthorizedStaff;

    private Purok $purok;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        config(['services.gemini.key' => 'test-api-key']);

        $perm = Permission::firstOrCreate(
            ['slug' => 'reports.log_phone'],
            ['label' => 'Record phone reports', 'group' => 'reports']
        );

        $this->authorizedStaff = User::factory()->staff()->create();
        $this->authorizedStaff->permissions()->attach($perm);

        $this->unauthorizedStaff = User::factory()->staff()->create();

        $this->purok = Purok::create(['name' => 'Purok 2']);
        $this->category = Category::create([
            'name' => 'Garbage Collection',
            'slug' => 'garbage',
            'is_active' => true,
        ]);
    }

    public function test_staff_analyze_endpoint_requires_auth_and_permission(): void
    {
        $file = UploadedFile::fake()->create('call.webm', 200, 'audio/webm');

        $this->postJson(route('admin.reports.phone.analyze'), ['audio' => $file])
            ->assertUnauthorized();

        $this->actingAs($this->unauthorizedStaff)
            ->postJson(route('admin.reports.phone.analyze'), ['audio' => $file])
            ->assertForbidden();
    }

    public function test_staff_analyze_endpoint_successful_full_extraction_with_gemini_fake(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'transcript' => 'Magandang umaga, ako si Maria Santos, 09171234567. May tambak na basura sa Purok 2 malapit sa elementary school.',
                                        'description' => 'May tambak na basura sa Purok 2 malapit sa elementary school na hindi nahahakot.',
                                        'language' => 'fil',
                                        'clarity' => 'clear',
                                        'caller_name' => 'Maria Santos',
                                        'caller_phone' => '09171234567',
                                        'caller_email' => 'maria@example.com',
                                        'wants_anonymous' => false,
                                        'purok' => 'Purok 2',
                                        'landmark' => 'Near elementary school',
                                        'category' => 'garbage',
                                        'urgency' => 'High',
                                        'emergency' => false,
                                        'not_stated' => [],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('call.webm', 300, 'audio/webm');

        $response = $this->actingAs($this->authorizedStaff)
            ->postJson(route('admin.reports.phone.analyze'), [
                'audio' => $file,
                'duration_seconds' => 45,
            ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => 'done',
                'clarity' => 'clear',
                'is_partial' => false,
                'is_unclear' => false,
                'transcript' => 'Magandang umaga, ako si Maria Santos, 09171234567. May tambak na basura sa Purok 2 malapit sa elementary school.',
                'description' => 'May tambak na basura sa Purok 2 malapit sa elementary school na hindi nahahakot.',
                'caller_name' => 'Maria Santos',
                'caller_phone' => '09171234567',
                'caller_email' => 'maria@example.com',
                'purok' => 'Purok 2',
                'purok_id' => $this->purok->id,
                'category' => 'garbage',
                'category_id' => $this->category->id,
                'urgency' => 'High',
                'emergency' => false,
            ]);

        $token = $response->json('token');
        $audio = ReportAudio::where('token', $token)->first();
        $this->assertNotNull($audio);
        $this->assertEquals(ReportAudio::ORIGIN_STAFF, $audio->origin);
        $this->assertEquals($this->authorizedStaff->id, $audio->uploaded_by);
        $this->assertFalse((bool) $audio->unverified_draft);

        // Ensure Gemini request payload contained only audio + allowed lists, no user/report data
        Http::assertSent(function ($request) {
            $data = $request->data();
            $systemText = data_get($data, 'systemInstruction.parts.0.text', '');
            $this->assertStringContainsString('Purok 2', $systemText);
            $this->assertStringContainsString('garbage', $systemText);
            $this->assertStringNotContainsString('Maria Santos', $systemText);
            $this->assertStringNotContainsString($this->authorizedStaff->email, $systemText);

            return true;
        });
    }

    public function test_partial_clarity_shows_notice_and_fills_stated_fields(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'transcript' => 'May tumutulong tubo [static] sa Purok 2...',
                                        'description' => 'May tumutulong tubo sa Purok 2.',
                                        'language' => 'fil',
                                        'clarity' => 'partial',
                                        'caller_name' => null,
                                        'caller_phone' => null,
                                        'caller_email' => null,
                                        'wants_anonymous' => false,
                                        'purok' => 'Purok 2',
                                        'landmark' => null,
                                        'category' => 'garbage',
                                        'urgency' => 'Low',
                                        'emergency' => false,
                                        'not_stated' => ['caller_name', 'caller_phone', 'caller_email', 'landmark'],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('partial.webm', 250, 'audio/webm');

        $response = $this->actingAs($this->authorizedStaff)
            ->postJson(route('admin.reports.phone.analyze'), ['audio' => $file]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'clarity' => 'partial',
                'is_partial' => true,
                'is_unclear' => false,
                'purok' => 'Purok 2',
            ]);

        $this->assertStringContainsString('Parts of this recording were unclear', $response->json('notice'));
        $this->assertContains('caller_phone', $response->json('not_stated'));
    }

    public function test_unclear_audio_or_invalid_json_sets_needs_verification_with_no_prefilled_fields(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'Not valid json or completely unintelligible',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('unintelligible.webm', 300, 'audio/webm');

        $response = $this->actingAs($this->authorizedStaff)
            ->postJson(route('admin.reports.phone.analyze'), ['audio' => $file]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => ReportAudio::STATUS_NEEDS_VERIFICATION,
                'clarity' => 'unclear',
                'is_unclear' => true,
                'caller_name' => null,
                'caller_phone' => null,
                'purok' => null,
                'category' => null,
                'urgency' => null,
                'description' => '',
            ]);

        $this->assertStringContainsString("couldn't understand this recording clearly", $response->json('notice'));
        $this->assertContains('purok', $response->json('not_stated'));
        $this->assertContains('description', $response->json('not_stated'));

        $token = $response->json('token');
        $audio = ReportAudio::where('token', $token)->first();
        $this->assertTrue((bool) $audio->unverified_draft);
    }

    public function test_gemini_timeout_returns_failed_status_with_no_prefilled_fields(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(null, 504),
        ]);

        $file = UploadedFile::fake()->create('timeout.webm', 200, 'audio/webm');

        $response = $this->actingAs($this->authorizedStaff)
            ->postJson(route('admin.reports.phone.analyze'), ['audio' => $file]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => ReportAudio::STATUS_FAILED,
                'clarity' => 'unclear',
                'purok' => null,
                'description' => '',
            ]);
    }

    public function test_phone_number_normalization_and_invalid_field_dropping(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'transcript' => 'Tumatawag si Ben, phone is +63 918 123 4567, purok 99, category invalid-slug, urgency SuperCritical.',
                                        'description' => 'May sira sa daan.',
                                        'language' => 'fil',
                                        'clarity' => 'clear',
                                        'caller_name' => 'Ben',
                                        'caller_phone' => '+63 918 123 4567',
                                        'caller_email' => 'invalid-email-address',
                                        'wants_anonymous' => false,
                                        'purok' => 'Purok 99 NonExistent',
                                        'landmark' => 'Near bridge',
                                        'category' => 'non-existent-category',
                                        'urgency' => 'SuperCritical',
                                        'emergency' => false,
                                        'not_stated' => [],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('test.webm', 200, 'audio/webm');

        $response = $this->actingAs($this->authorizedStaff)
            ->postJson(route('admin.reports.phone.analyze'), ['audio' => $file]);

        $response->assertOk()
            ->assertJson([
                'caller_phone' => '09181234567', // Normalized to standard 11 digits
                'caller_email' => null,          // Invalid email dropped
                'purok' => null,                 // Non-existent purok dropped
                'category' => null,              // Non-existent category dropped
                'urgency' => null,               // Non-existent urgency dropped
            ]);

        $notStated = $response->json('not_stated');
        $this->assertContains('caller_email', $notStated);
        $this->assertContains('purok', $notStated);
        $this->assertContains('category', $notStated);
        $this->assertContains('urgency', $notStated);
    }

    public function test_resident_analyze_endpoint_never_returns_raw_transcript_or_category_urgency(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'transcript' => 'Hello ako si Juan, may sirang ilaw sa Purok 2.',
                                        'description' => 'May sirang ilaw sa Purok 2.',
                                        'language' => 'fil',
                                        'clarity' => 'clear',
                                        'caller_name' => 'Juan Dela Cruz',
                                        'caller_phone' => '09171234567',
                                        'caller_email' => null,
                                        'wants_anonymous' => false,
                                        'purok' => 'Purok 2',
                                        'landmark' => null,
                                        'category' => 'garbage',
                                        'urgency' => 'Medium',
                                        'emergency' => false,
                                        'not_stated' => [],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('resident.webm', 200, 'audio/webm');

        $response = $this->postJson(route('reports.voice.analyze'), [
            'audio' => $file,
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'purok' => 'Purok 2',
                'category' => null,
                'category_id' => null,
                'urgency' => null,
            ]);

        // Assert raw transcript is strictly excluded from resident response
        $this->assertArrayNotHasKey('transcript', $response->json());
    }

    public function test_resident_anonymous_drop_strips_contact_details(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'transcript' => 'Ako si Juan 09171234567 pero huwag niyo sabihin pangalan ko.',
                                        'description' => 'May nasirang kalsada sa Purok 2.',
                                        'language' => 'fil',
                                        'clarity' => 'clear',
                                        'caller_name' => 'Juan Dela Cruz',
                                        'caller_phone' => '09171234567',
                                        'caller_email' => 'juan@example.com',
                                        'wants_anonymous' => true,
                                        'purok' => 'Purok 2',
                                        'landmark' => null,
                                        'category' => 'garbage',
                                        'urgency' => 'Low',
                                        'emergency' => false,
                                        'not_stated' => [],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('anon.webm', 200, 'audio/webm');

        $response = $this->postJson(route('reports.voice.analyze'), [
            'audio' => $file,
            'anonymous' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'caller_name' => null,
                'caller_phone' => null,
                'caller_email' => null,
                'wants_anonymous' => true,
            ]);
    }

    public function test_abuse_limits_reject_invalid_mime_and_excessive_duration(): void
    {
        // 1. Invalid MIME type (text file disguised as audio)
        $textFake = UploadedFile::fake()->create('malicious.webm', 100, 'text/plain');

        $this->postJson(route('reports.voice.analyze'), ['audio' => $textFake])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['audio']);

        // 2. Duration exceeding 120s
        $audioFile = UploadedFile::fake()->create('long.webm', 200, 'audio/webm');

        $this->postJson(route('reports.voice.analyze'), [
            'audio' => $audioFile,
            'duration_seconds' => 125,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['duration_seconds']);
    }

    public function test_resident_recording_disabled_setting_returns_fallback_without_gemini_call(): void
    {
        Setting::updateOrCreate(
            ['key' => 'voice.resident_recording_enabled'],
            ['value' => 'false', 'type' => 'boolean']
        );

        $file = UploadedFile::fake()->create('disabled.webm', 200, 'audio/webm');

        Http::fake();

        $response = $this->postJson(route('reports.voice.analyze'), ['audio' => $file]);

        $response->assertOk()
            ->assertJson([
                'ok' => false,
                'status' => 'disabled',
                'fallback' => true,
            ]);

        Http::assertNothingSent();
    }

    public function test_daily_limit_reached_returns_friendly_fallback_without_gemini_call(): void
    {
        Setting::updateOrCreate(
            ['key' => 'voice.daily_analyze_limit'],
            ['value' => '1', 'type' => 'integer']
        );

        // Create 1 existing analysis today to reach the limit
        ReportAudio::create([
            'token' => '11111111-1111-1111-1111-111111111111',
            'origin' => ReportAudio::ORIGIN_RESIDENT,
            'disk_path' => 'audio/2026/11/fake.webm',
            'mime' => 'audio/webm',
            'size_bytes' => 100,
            'transcript_status' => ReportAudio::STATUS_DONE,
            'created_at' => now(),
        ]);

        $file = UploadedFile::fake()->create('limit.webm', 200, 'audio/webm');

        Http::fake();

        $response = $this->postJson(route('reports.voice.analyze'), ['audio' => $file]);

        $response->assertOk()
            ->assertJson([
                'ok' => false,
                'status' => 'limit_reached',
                'fallback' => true,
            ]);

        Http::assertNothingSent();
    }

    public function test_mock_scenarios_return_realistic_canned_extractions(): void
    {
        $file = UploadedFile::fake()->create('mock.webm', 200, 'audio/webm');

        // Emergency scenario
        $resEmergency = $this->actingAs($this->authorizedStaff)
            ->postJson(route('admin.reports.phone.analyze'), [
                'audio' => $file,
                'mock_scenario' => 'emergency',
            ]);

        $resEmergency->assertOk()
            ->assertJson([
                'emergency' => true,
                'urgency' => 'Critical',
                'clarity' => 'clear',
            ]);

        // Unclear scenario
        $resUnclear = $this->actingAs($this->authorizedStaff)
            ->postJson(route('admin.reports.phone.analyze'), [
                'audio' => $file,
                'mock_scenario' => 'unclear',
            ]);

        $resUnclear->assertOk()
            ->assertJson([
                'status' => ReportAudio::STATUS_NEEDS_VERIFICATION,
                'clarity' => 'unclear',
                'is_unclear' => true,
                'purok' => null,
            ]);
    }

    public function test_attached_audio_retention_counted_from_report_closure(): void
    {
        $retentionDays = 90;

        // 1. Attached to open report (> 90 days from audio creation) -> Should NOT be purged
        $openReport = Report::factory()->create(['status' => 'in_progress']);
        $openAudioPath = 'audio/2026/01/open.webm';
        Storage::disk('private')->put($openAudioPath, 'open-audio');
        $openAudio = ReportAudio::create([
            'token' => '22222222-2222-2222-2222-222222222222',
            'origin' => ReportAudio::ORIGIN_RESIDENT,
            'disk_path' => $openAudioPath,
            'mime' => 'audio/webm',
            'size_bytes' => 100,
            'report_id' => $openReport->id,
            'expires_at' => now()->subDay(),
        ]);

        // 2. Attached to closed report closed recently (10 days ago) -> Should NOT be purged
        $recentlyClosed = Report::factory()->create([
            'status' => 'resolved',
            'resolved_at' => now()->subDays(10),
        ]);
        $recentClosedPath = 'audio/2026/01/recent.webm';
        Storage::disk('private')->put($recentClosedPath, 'recent-audio');
        $recentAudio = ReportAudio::create([
            'token' => '33333333-3333-3333-3333-333333333333',
            'origin' => ReportAudio::ORIGIN_RESIDENT,
            'disk_path' => $recentClosedPath,
            'mime' => 'audio/webm',
            'size_bytes' => 100,
            'report_id' => $recentlyClosed->id,
            'expires_at' => now()->subDay(),
        ]);

        // 3. Attached to closed report closed 95 days ago -> SHOULD be purged
        $oldClosed = Report::factory()->create([
            'status' => 'resolved',
            'resolved_at' => now()->subDays(95),
        ]);
        $oldClosedPath = 'audio/2026/01/old.webm';
        Storage::disk('private')->put($oldClosedPath, 'old-audio');
        $oldAudio = ReportAudio::create([
            'token' => '44444444-4444-4444-4444-444444444444',
            'origin' => ReportAudio::ORIGIN_RESIDENT,
            'disk_path' => $oldClosedPath,
            'mime' => 'audio/webm',
            'size_bytes' => 100,
            'report_id' => $oldClosed->id,
            'expires_at' => now()->subDay(),
        ]);

        $purgedCount = app(VoiceService::class)->purgeExpired();

        $this->assertEquals(1, $purgedCount);
        Storage::disk('private')->assertExists($openAudioPath);
        Storage::disk('private')->assertExists($recentClosedPath);
        Storage::disk('private')->assertMissing($oldClosedPath);
        $this->assertEquals('[purged]', $oldAudio->fresh()->disk_path);
    }
}
