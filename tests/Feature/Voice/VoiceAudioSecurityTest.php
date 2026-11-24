<?php

namespace Tests\Feature\Voice;

use App\Models\Category;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportAudio;
use App\Models\User;
use App\Services\ReportReviewService;
use App\Services\Voice\VoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceAudioSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Report $report;

    private ReportAudio $audio;

    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        $permView = Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View reports', 'group' => 'reports']);
        $this->staff = User::factory()->staff()->create();
        $this->staff->permissions()->attach($permView);

        $purok = Purok::factory()->create();
        $category = Category::create([
            'name' => 'Roads',
            'slug' => 'roads',
            'is_active' => true,
        ]);
        $this->office = Office::create([
            'name' => 'Engineering',
            'slug' => 'engineering',
            'type' => 'barangay',
            'is_internal' => true,
            'is_active' => true,
        ]);

        $this->report = Report::factory()->create([
            'source' => Report::SOURCE_VOICE,
            'status' => 'submitted',
            'purok_id' => $purok->id,
            'category_id' => $category->id,
            'urgency' => 'Medium',
            'description' => '(Voice recording attached — needs staff audio verification)',
        ]);

        $audioFile = UploadedFile::fake()->create('voice.webm', 250, 'audio/webm');
        $this->audio = app(VoiceService::class)->storeAudio($audioFile, ReportAudio::ORIGIN_RESIDENT);
        $this->audio->update([
            'report_id' => $this->report->id,
            'transcript' => '',
            'transcript_status' => ReportAudio::STATUS_NEEDS_VERIFICATION,
            'unverified_draft' => true,
        ]);
    }

    public function test_guest_cannot_stream_private_audio(): void
    {
        $this->get(route('admin.reports.audio', $this->report))
            ->assertRedirect('/login');
    }

    public function test_staff_can_stream_private_audio_with_no_store_headers(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('admin.reports.audio', $this->report));

        $response->assertOk();
        $this->assertStringContainsString('webm', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    public function test_routing_is_blocked_by_review_gate_while_audio_is_unverified(): void
    {
        $this->actingAs($this->staff);

        // Attempting to route directly
        $response = $this->patch(route('admin.reports.route', $this->report), [
            'office_id' => $this->office->id,
        ]);

        $response->assertSessionHas('error');
        $this->report->refresh();
        $this->assertEquals('submitted', $this->report->status);

        // Attempting via ReportReviewService
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Voice report needs verification');

        app(ReportReviewService::class)->save($this->report, [
            'category_id' => $this->report->category_id,
            'urgency' => 'Medium',
            'staff_description' => 'Unverified test',
            'office_id' => $this->office->id,
        ], $this->staff, 'route');
    }

    public function test_staff_verification_updates_description_clears_gate_and_logs_audit(): void
    {
        $verifyRes = $this->actingAs($this->staff)
            ->post(route('admin.reports.audio.verify', $this->report), [
                'description' => 'Staff listened: Verified deep pothole near basketball court.',
                'verification_note' => 'Audio verified via staff headset.',
            ]);

        $verifyRes->assertSessionHas('toast');

        $this->report->refresh();
        $this->audio->refresh();

        $this->assertEquals('Staff listened: Verified deep pothole near basketball court.', $this->report->description);
        $this->assertTrue($this->audio->isVerified());
        $this->assertEquals($this->staff->id, $this->audio->verified_by);
        $this->assertFalse($this->audio->unverified_draft);
        $this->assertFalse($this->report->hasUnverifiedAudio());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'voice.audio_verified',
            'model_id' => $this->report->id,
            'user_id' => $this->staff->id,
        ]);

        // Now routing succeeds
        $routeRes = $this->patch(route('admin.reports.route', $this->report), [
            'office_id' => $this->office->id,
        ]);

        $routeRes->assertSessionHas('success');
        $this->report->refresh();
        $this->assertEquals('routed', $this->report->status);
    }
}
