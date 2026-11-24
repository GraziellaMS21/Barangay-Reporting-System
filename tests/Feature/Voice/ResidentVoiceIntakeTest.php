<?php

namespace Tests\Feature\Voice;

use App\Models\Category;
use App\Models\FiscalYear;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportAudio;
use App\Models\Setting;
use App\Models\User;
use App\Services\PriorityService;
use App\Services\RoutingService;
use App\Services\Voice\VoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResidentVoiceIntakeTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Purok $purok;

    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->completeSetup();

        Storage::fake('private');
        Storage::fake('local');

        $this->category = Category::create([
            'name' => 'Garbage Collection',
            'slug' => 'garbage',
            'is_active' => true,
        ]);

        $this->purok = Purok::create(['name' => 'Purok 2']);

        $perm = Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View reports', 'group' => 'reports']);
        $this->staffUser = User::factory()->create(['role' => 'staff']);
        $this->staffUser->permissions()->attach($perm);
    }

    public function test_resident_can_submit_report_without_category(): void
    {
        $response = $this->post(route('reports.store'), [
            'has_consent_field' => '1',
            'consent' => '1',
            'description' => 'May malaking tambak ng basura sa tapat ng paaralan.',
            'purok_id' => $this->purok->id,
            'category_id' => null, // Optional category
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reports', [
            'description' => 'May malaking tambak ng basura sa tapat ng paaralan.',
            'category_id' => null,
            'resident_category_id' => null,
            'purok_id' => $this->purok->id,
        ]);

        $report = Report::latest('id')->first();
        $this->assertNull($report->category_id);

        // Routing service handles null category gracefully
        $routingDecision = app(RoutingService::class)->decisionFor($report);
        $this->assertNull($routingDecision['office']);
        $this->assertEquals('Category not yet assigned.', $routingDecision['jurisdiction_note']);

        // Priority calculation handles null category gracefully
        $score = app(PriorityService::class)->calculate($report);
        $this->assertIsInt($score);
    }

    public function test_resident_create_page_displays_voice_strip_and_optional_category(): void
    {
        $response = $this->get(route('reports.create'));

        $response->assertOk();
        $response->assertSee('Quick report by voice');
        $response->assertSee('or fill in the form yourself below', false);
        $response->assertSee('My recording is processed by AI and seen by barangay staff only');
        $response->assertSee('I agree, start recording');
        $response->assertSee('Choose a category (optional)');
        $response->assertDontSee('Choose a category *');
    }

    public function test_voice_strip_is_hidden_when_resident_recording_is_disabled(): void
    {
        Setting::updateOrCreate(
            ['key' => 'voice.resident_recording_enabled'],
            ['value' => 'false', 'type' => 'boolean']
        );

        $response = $this->get(route('reports.create'));

        $response->assertOk();
        $response->assertDontSee('Quick report by voice');
    }

    public function test_resident_voice_analyze_includes_category_and_contact_when_enabled(): void
    {
        Setting::updateOrCreate(
            ['key' => 'voice.resident_extract_contact'],
            ['value' => 'true', 'type' => 'boolean']
        );

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'transcript' => 'Ako po si Maria Santos, 09171234567, may tambak ng basura sa Purok 2.',
                                        'description' => 'May tambak ng basura sa Purok 2.',
                                        'language' => 'fil',
                                        'clarity' => 'clear',
                                        'caller_name' => 'Maria Santos',
                                        'caller_phone' => '09171234567',
                                        'caller_email' => null,
                                        'wants_anonymous' => false,
                                        'purok' => 'Purok 2',
                                        'landmark' => 'Near Chapel',
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
                'purok_id' => $this->purok->id,
                'category' => 'garbage',
                'category_id' => $this->category->id,
                'caller_name' => 'Maria Santos',
                'caller_phone' => '09171234567',
                'urgency' => null, // Urgency is strictly excluded from resident response
            ]);

        // Raw transcript is NEVER returned in resident response
        $this->assertArrayNotHasKey('transcript', $response->json());

        // Check report_audios stored extraction_data and ai_description
        $audio = ReportAudio::where('token', $response->json('token'))->firstOrFail();
        $this->assertNotNull($audio->extracted_data);
        $this->assertEquals('May tambak ng basura sa Purok 2.', $audio->ai_description);
        $this->assertEquals('garbage', $audio->extracted_data['category']);
        // Extracted data does NOT persist contact details for unattached audio
        $this->assertArrayNotHasKey('caller_name', $audio->extracted_data);
        $this->assertArrayNotHasKey('caller_phone', $audio->extracted_data);
    }

    public function test_resident_voice_analyze_strips_contact_when_anonymous_is_toggled(): void
    {
        Setting::updateOrCreate(
            ['key' => 'voice.resident_extract_contact'],
            ['value' => 'true', 'type' => 'boolean']
        );

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'transcript' => 'Ako po si Maria Santos, 09171234567, may tambak ng basura sa Purok 2.',
                                        'description' => 'May tambak ng basura sa Purok 2.',
                                        'language' => 'fil',
                                        'clarity' => 'clear',
                                        'caller_name' => 'Maria Santos',
                                        'caller_phone' => '09171234567',
                                        'caller_email' => 'maria@example.com',
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
            'anonymous' => 1,
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'caller_name' => null,
                'caller_phone' => null,
                'caller_email' => null,
            ]);
    }

    public function test_resident_report_stores_provenance_and_displays_chips_on_review_page(): void
    {
        // 1. Create a ReportAudio record simulating analysis
        $voiceService = app(VoiceService::class);
        $audioFile = UploadedFile::fake()->create('sample.webm', 100, 'audio/webm');
        $audio = $voiceService->storeAudio(
            file: $audioFile,
            origin: ReportAudio::ORIGIN_RESIDENT,
            userId: null,
            durationSeconds: 30
        );
        $audio->update([
            'transcript' => 'May sirang poste ng ilaw sa kanto ng Purok 2.',
            'ai_description' => 'Sirang poste ng ilaw sa kanto.',
            'transcript_status' => ReportAudio::STATUS_DONE,
            'extracted_data' => [
                'description' => 'Sirang poste ng ilaw sa kanto.',
                'category' => 'garbage',
                'category_id' => $this->category->id,
                'purok' => 'Purok 2',
                'purok_id' => $this->purok->id,
                'landmark' => 'Near Chapel',
                'emergency' => false,
            ],
        ]);

        // 2. Resident submits report, accepting category & purok from voice, but editing description
        $response = $this->post(route('reports.store'), [
            'has_consent_field' => '1',
            'consent' => '1',
            'audio_token' => $audio->token,
            'description' => 'Sirang poste ng ilaw sa kanto — kumikislap at delikado.', // Edited by resident
            'category_id' => $this->category->id, // Kept from voice
            'purok_id' => $this->purok->id, // Kept from voice
            'landmark' => 'Near Chapel', // Kept from voice
            'voice_fields_filled' => ['description', 'category', 'purok', 'landmark'],
        ]);

        $response->assertRedirect();
        $report = Report::latest('id')->firstOrFail();
        $this->assertEquals(Report::SOURCE_VOICE, $report->source);

        // 3. Verify provenance stored on ReportAudio
        $audio->refresh();
        $this->assertEquals($report->id, $audio->report_id);
        $this->assertIsArray($audio->provenance);
        $this->assertTrue($audio->provenance['description']['filled']);
        $this->assertTrue($audio->provenance['description']['edited']); // Different from ai_description
        $this->assertTrue($audio->provenance['category']['filled']);
        $this->assertFalse($audio->provenance['category']['edited']); // Same category
        $this->assertTrue($audio->provenance['purok']['filled']);
        $this->assertFalse($audio->provenance['purok']['edited']); // Same purok

        // 4. Staff review page renders chips
        $reviewResponse = $this->actingAs($this->staffUser)
            ->get(route('admin.reports.show', $report));

        $reviewResponse->assertOk();
        $reviewResponse->assertSee('From recording');
        $reviewResponse->assertSee('Edited after recording');
        $reviewResponse->assertSee('Edited by resident');
    }

    public function test_admin_review_page_displays_not_chosen_by_resident_when_category_is_null(): void
    {
        $fiscalYear = FiscalYear::where('status', 'active')->first() ?? FiscalYear::create([
            'year' => (int) date('Y'),
            'name' => 'FY '.date('Y'),
            'status' => 'active',
            'start_date' => date('Y-01-01'),
            'end_date' => date('Y-12-31'),
        ]);

        $report = Report::create([
            'fiscal_year_id' => $fiscalYear->id,
            'tracking_code' => Report::generateTrackingCode(),
            'source' => Report::SOURCE_WEB,
            'status' => 'submitted',
            'description' => 'Nawalan ng tubig ang buong block 3.',
            'category_id' => null,
            'resident_category_id' => null,
            'purok_id' => $this->purok->id,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('admin.reports.show', $report));

        $response->assertOk();
        $response->assertSee('Not chosen by resident');
    }
}
