<?php

namespace Tests\Feature\Voice;

use App\Models\Category;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportAudio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VoiceReviewAndVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected Purok $purok;

    protected Category $category;

    protected Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        $pView = Permission::firstOrCreate(
            ['slug' => 'reports.view'],
            ['name' => 'View reports', 'label' => 'View reports', 'group' => 'reports']
        );
        $pLogPhone = Permission::firstOrCreate(
            ['slug' => 'reports.log_phone'],
            ['name' => 'Log phone reports', 'label' => 'Log phone reports', 'group' => 'reports']
        );

        $this->staffUser = User::factory()->create([
            'role' => 'staff',
        ]);
        $this->staffUser->permissions()->attach([$pView->id, $pLogPhone->id]);

        $this->purok = Purok::firstOrCreate(['name' => 'Purok 1']);
        $this->category = Category::firstOrCreate(
            ['slug' => 'drainage'],
            ['name' => 'Drainage', 'is_active' => true]
        );
        $this->office = Office::firstOrCreate(
            ['name' => 'Barangay Works & Drainage'],
            ['type' => 'internal', 'is_active' => true]
        );
    }

    protected function createReportAudio(Report $report, array $attributes = []): ReportAudio
    {
        return ReportAudio::create(array_merge([
            'report_id' => $report->id,
            'token' => (string) Str::uuid(),
            'origin' => ReportAudio::ORIGIN_RESIDENT,
            'disk_path' => 'audio/test.webm',
            'mime' => 'audio/webm',
            'size_bytes' => 1024,
            'transcript_status' => ReportAudio::STATUS_DONE,
        ], $attributes));
    }

    public function test_review_page_displays_not_provided_for_null_contact_and_location_fields(): void
    {
        $report = Report::factory()->create([
            'source' => Report::SOURCE_VOICE,
            'is_anonymous' => false,
            'reporter_name' => null,
            'reporter_contact' => null,
            'reporter_email' => null,
            'purok_id' => null,
            'landmark' => null,
            'category_id' => null,
            'status' => 'submitted',
            'description' => 'Muffled sound of water rushing under sidewalk.',
        ]);

        $this->createReportAudio($report, [
            'origin' => ReportAudio::ORIGIN_RESIDENT,
            'transcript_status' => ReportAudio::STATUS_DONE,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('admin.reports.show', $report));

        $response->assertOk();
        $response->assertSee('Voice report');
        $response->assertSee('Not provided');
        $response->assertSee('Not chosen by resident');
    }

    public function test_review_page_displays_source_badges_accurately(): void
    {
        $voiceReport = Report::factory()->create([
            'source' => Report::SOURCE_VOICE,
            'description' => 'Voice reported issue.',
        ]);

        $phoneReport = Report::factory()->create([
            'source' => Report::SOURCE_PHONE_LOG,
            'description' => 'Phone intake reported issue.',
        ]);

        $this->actingAs($this->staffUser)
            ->get(route('admin.reports.show', $voiceReport))
            ->assertOk()
            ->assertSee('Voice report');

        $this->actingAs($this->staffUser)
            ->get(route('admin.reports.show', $phoneReport))
            ->assertOk()
            ->assertSee('Phone intake');
    }

    public function test_review_page_displays_needs_verification_banner_and_gating(): void
    {
        $report = Report::factory()->create([
            'source' => Report::SOURCE_VOICE,
            'status' => 'submitted',
            'category_id' => $this->category->id,
            'urgency' => 'Medium',
            'description' => '(Voice recording attached — needs staff audio verification)',
        ]);

        $this->createReportAudio($report, [
            'origin' => ReportAudio::ORIGIN_RESIDENT,
            'transcript_status' => ReportAudio::STATUS_NEEDS_VERIFICATION,
            'unverified_draft' => true,
            'verified_at' => null,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('admin.reports.show', $report));

        $response->assertOk();
        $response->assertSee('Voice Audio Needs Verification');
        $response->assertSee('Needs verification');
        $response->assertSee('Staff Verified Description');
        $response->assertSee('data-has-unverified-audio="true"', false);
    }

    public function test_routing_attempt_is_strictly_blocked_when_audio_needs_verification(): void
    {
        $report = Report::factory()->create([
            'source' => Report::SOURCE_VOICE,
            'status' => 'submitted',
            'category_id' => $this->category->id,
            'urgency' => 'Medium',
            'office_id' => $this->office->id,
            'description' => '(Voice recording attached — needs staff audio verification)',
        ]);

        $this->createReportAudio($report, [
            'origin' => ReportAudio::ORIGIN_RESIDENT,
            'transcript_status' => ReportAudio::STATUS_NEEDS_VERIFICATION,
            'unverified_draft' => true,
            'verified_at' => null,
        ]);

        $this->assertTrue($report->hasUnverifiedAudio());

        $response = $this->actingAs($this->staffUser)
            ->from(route('admin.reports.show', $report))
            ->patch(route('admin.reports.route', $report), [
                'office_id' => $this->office->id,
                'assigned_to' => $this->staffUser->id,
            ]);

        $response->assertSessionHas('error', 'Voice report needs verification. Listen to the recording and confirm or enter what was said before routing.');
        $report->refresh();
        $this->assertSame('submitted', $report->status);
    }

    public function test_staff_can_verify_audio_and_unblock_routing(): void
    {
        $report = Report::factory()->create([
            'source' => Report::SOURCE_VOICE,
            'status' => 'submitted',
            'category_id' => $this->category->id,
            'urgency' => 'Medium',
            'description' => '(Voice recording attached — needs staff audio verification)',
        ]);

        $audio = $this->createReportAudio($report, [
            'origin' => ReportAudio::ORIGIN_RESIDENT,
            'transcript_status' => ReportAudio::STATUS_NEEDS_VERIFICATION,
            'unverified_draft' => true,
            'verified_at' => null,
        ]);

        $verifyResponse = $this->actingAs($this->staffUser)
            ->from(route('admin.reports.show', $report))
            ->post(route('admin.reports.audio.verify', $report), [
                'description' => 'Verified leak flooding street near chapel.',
            ]);

        $verifyResponse->assertRedirect();
        $verifyResponse->assertSessionHas('success', 'Voice audio verified and report description updated.');

        $report->refresh();
        $audio->refresh();

        $this->assertFalse($report->hasUnverifiedAudio());
        $this->assertNotNull($audio->verified_at);
        $this->assertSame($this->staffUser->id, $audio->verified_by);
        $this->assertFalse((bool) $audio->unverified_draft);
        $this->assertSame('Verified leak flooding street near chapel.', $report->description);

        // Now routing succeeds
        $routeResponse = $this->actingAs($this->staffUser)
            ->from(route('admin.reports.show', $report))
            ->patch(route('admin.reports.route', $report), [
                'office_id' => $this->office->id,
                'assigned_to' => $this->staffUser->id,
            ]);

        $routeResponse->assertRedirect();
        $report->refresh();
        $this->assertSame('routed', $report->status);
    }

    public function test_review_page_displays_provenance_badges(): void
    {
        $report = Report::factory()->create([
            'source' => Report::SOURCE_VOICE,
            'category_id' => $this->category->id,
            'purok_id' => $this->purok->id,
            'landmark' => 'Near Chapel',
            'description' => 'Original resident modified description after recording.',
        ]);

        $this->createReportAudio($report, [
            'ai_description' => 'Original voice transcription before citizen edit.',
            'provenance' => [
                'category' => ['filled' => true, 'edited' => false],
                'purok' => ['filled' => true, 'edited' => false],
                'landmark' => ['filled' => true, 'edited' => true],
            ],
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('admin.reports.show', $report));

        $response->assertOk();
        $response->assertSee('From recording');
        $response->assertSee('Edited after recording');
        $response->assertSee('Edited by resident');
    }

    public function test_csv_export_keeps_unstated_fields_blank_and_never_outputs_not_provided(): void
    {
        $report = Report::factory()->create([
            'tracking_code' => 'BRG-EXPTEST01',
            'source' => Report::SOURCE_VOICE,
            'is_anonymous' => false,
            'reporter_name' => null,
            'category_id' => null,
            'purok_id' => null,
            'urgency' => null,
            'description' => 'Test CSV voice export report without unstated fields.',
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('admin.reports.export'));

        $response->assertOk();
        $csvContent = $response->streamedContent();

        $this->assertStringContainsString('BRG-EXPTEST01', $csvContent);
        // Ensure CSV never prints UI placeholder "Not provided"
        $this->assertStringNotContainsString('Not provided', $csvContent);
    }
}
