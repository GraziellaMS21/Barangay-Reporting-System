<?php

namespace Tests\Feature\Voice;

use App\Models\Category;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportAudio;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceTranscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Purok $purok;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('local');

        $this->purok = Purok::factory()->create();
        $this->category = Category::create([
            'name' => 'Drainage',
            'slug' => 'drainage',
            'is_active' => true,
        ]);
    }

    public function test_resident_can_upload_audio_and_receive_transcription(): void
    {
        $file = UploadedFile::fake()->create('report.webm', 500, 'audio/webm');

        $response = $this->postJson(route('reports.voice.transcribe'), [
            'audio' => $file,
            'duration_seconds' => 30,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'transcript',
                'status',
                'unverified_draft',
                'language',
                'unclear',
                'notice',
            ]);

        $token = $response->json('token');
        $this->assertDatabaseHas('report_audios', [
            'token' => $token,
            'origin' => ReportAudio::ORIGIN_RESIDENT,
            'duration_seconds' => 30,
        ]);

        $audio = ReportAudio::where('token', $token)->first();
        Storage::disk('private')->assertExists($audio->disk_path);
    }

    public function test_transcription_endpoint_respects_voice_enabled_setting(): void
    {
        config(['voice.enabled' => false]);

        $file = UploadedFile::fake()->create('report.webm', 200, 'audio/webm');

        $response = $this->postJson(route('reports.voice.transcribe'), [
            'audio' => $file,
        ]);

        $response->assertStatus(403);
    }

    public function test_resident_report_submission_links_audio_token_and_sets_voice_source(): void
    {
        $file = UploadedFile::fake()->create('report.webm', 300, 'audio/webm');
        $transcribeRes = $this->postJson(route('reports.voice.transcribe'), [
            'audio' => $file,
        ]);
        $token = $transcribeRes->json('token');

        $submitRes = $this->post(route('reports.store'), [
            'category_id' => $this->category->id,
            'description' => 'May baradong kanal dito sa kanto ng purok.',
            'purok_id' => $this->purok->id,
            'landmark' => 'Sari-sari store',
            'audio_token' => $token,
            'consent' => '1',
            'has_consent_field' => '1',
        ]);

        $submitRes->assertRedirect();

        $report = Report::latest('id')->first();
        $this->assertNotNull($report);
        $this->assertEquals(Report::SOURCE_VOICE, $report->source);

        $this->assertDatabaseHas('report_audios', [
            'token' => $token,
            'report_id' => $report->id,
        ]);
    }

    public function test_unclear_audio_sets_unverified_draft_and_returns_notice(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('transcribeAudio')->andReturn([
                'transcript' => '',
                'language' => null,
                'unclear' => true,
                'confidence_note' => 'Muffled audio, speech unintelligible',
                'status' => ReportAudio::STATUS_NEEDS_VERIFICATION,
            ]);
        });

        $file = UploadedFile::fake()->create('muffled.webm', 400, 'audio/webm');

        $response = $this->postJson(route('reports.voice.transcribe'), [
            'audio' => $file,
        ]);

        $response->assertOk()
            ->assertJson([
                'unclear' => true,
                'unverified_draft' => true,
                'status' => ReportAudio::STATUS_NEEDS_VERIFICATION,
            ]);

        $this->assertStringContainsString('staff will listen', $response->json('notice'));
    }
}
