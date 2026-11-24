<?php

namespace Tests\Feature\Voice;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportAudio;
use App\Models\User;
use App\Services\Voice\VoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffPhoneReportIntakeUiTest extends TestCase
{
    use RefreshDatabase;

    private User $authorizedStaff;

    private Purok $purok;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        $perm = Permission::firstOrCreate(
            ['slug' => 'reports.log_phone'],
            ['label' => 'Record phone reports', 'group' => 'reports']
        );

        $this->authorizedStaff = User::factory()->staff()->create();
        $this->authorizedStaff->permissions()->attach($perm);

        $this->purok = Purok::create(['name' => 'Purok 1']);
        $this->category = Category::create([
            'name' => 'Road Damage',
            'slug' => 'road-damage',
            'is_active' => true,
        ]);
    }

    public function test_record_first_layout_renders_step_1_at_top_with_consent_gate_and_script(): void
    {
        $response = $this->actingAs($this->authorizedStaff)
            ->get(route('admin.reports.phone.create'));

        $response->assertOk();

        // 1. Step 1 is at the top as primary action
        $response->assertSee('Step 1: Primary Action');
        $response->assertSee('Record the call (speakerphone) or dictate a summary');
        $response->assertDontSee('Voice Recording / Audio Memo (optional)');

        // 2. Secondary link "Fill in manually instead"
        $response->assertSee('Fill in manually instead');
        $response->assertSee('#step-2-form');

        // 3. Verbal consent checkbox & spoken script
        $response->assertSee('Caller Verbal Consent Obtained');
        $response->assertSee('Spoken script to read aloud to the caller:');
        $response->assertSee('Barangay Calarian is officially recording this call to log your report');
        $response->assertSee('Check the verbal consent box above to unlock audio recording');

        // 4. Step 2 follows Step 1
        $response->assertSee('Step 2: Verification');
        $response->assertSee('Check and confirm the details');
        $response->assertSee('From recording');
    }

    public function test_form_renders_not_provided_placeholders_and_staff_transcript_panel(): void
    {
        $response = $this->actingAs($this->authorizedStaff)
            ->get(route('admin.reports.phone.create'));

        $response->assertOk();

        // Placeholders show "Not provided"
        $response->assertSee('placeholder="Not provided"', false);

        // Staff-only transcript panel
        $response->assertSee('staff-transcript-panel');
        $response->assertSee('What was said (Staff-only verbatim transcript)');
        $response->assertSee('Staff only');

        // Dynamic clarity banners
        $response->assertSee('banner-partial-clarity');
        $response->assertSee('Parts of this recording were unclear');
        $response->assertSee('banner-unclear-clarity');
        $response->assertSee("We couldn't understand this recording clearly", false);
        $response->assertSee('banner-emergency-detected');
    }

    public function test_submitting_form_with_audio_and_consent_stores_recorder_user_id(): void
    {
        $audioFile = UploadedFile::fake()->create('call.webm', 300, 'audio/webm');
        $audio = app(VoiceService::class)->storeAudio($audioFile, ReportAudio::ORIGIN_STAFF, $this->authorizedStaff->id);

        $response = $this->actingAs($this->authorizedStaff)
            ->post(route('admin.reports.phone.store'), [
                'verbal_consent' => '1',
                'caller_name' => 'Juan Dela Cruz',
                'caller_phone' => '09171234567',
                'purok_id' => $this->purok->id,
                'landmark' => 'Near Chapel',
                'category_id' => $this->category->id,
                'urgency' => 'High',
                'description' => 'Malalim na lubak sa gitna ng kalsada delikado sa mga motor.',
                'audio_token' => $audio->token,
                'is_emergency' => '1',
            ]);

        $report = Report::latest('id')->first();
        $this->assertNotNull($report);
        $response->assertRedirect(route('admin.reports.show', $report));

        $this->assertEquals(Report::SOURCE_PHONE_LOG, $report->source);
        $this->assertEquals('emergency_flagged', $report->status);
        $this->assertTrue($report->is_emergency);
        $this->assertEquals('Juan Dela Cruz', $report->reporter_name);
        $this->assertEquals('09171234567', $report->reporter_contact);
        $this->assertEquals($this->authorizedStaff->id, $report->consent_recorded_by);
        $this->assertNotNull($report->consent_at);

        // Audio record linked
        $this->assertEquals($report->id, $audio->fresh()->report_id);
    }
}
