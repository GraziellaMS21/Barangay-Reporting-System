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

class PhoneReportIntakeTest extends TestCase
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

        $perm = Permission::firstOrCreate(
            ['slug' => 'reports.log_phone'],
            ['label' => 'Record phone reports', 'group' => 'reports']
        );

        $this->authorizedStaff = User::factory()->staff()->create();
        $this->authorizedStaff->permissions()->attach($perm);

        $this->unauthorizedStaff = User::factory()->staff()->create();

        $this->purok = Purok::factory()->create();
        $this->category = Category::create([
            'name' => 'Waste Management',
            'slug' => 'waste-management',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.reports.phone.create'))
            ->assertRedirect('/login');
    }

    public function test_unauthorized_staff_receives_403(): void
    {
        $this->actingAs($this->unauthorizedStaff)
            ->get(route('admin.reports.phone.create'))
            ->assertForbidden();
    }

    public function test_authorized_staff_can_view_phone_intake_form(): void
    {
        $this->actingAs($this->authorizedStaff)
            ->get(route('admin.reports.phone.create'))
            ->assertOk()
            ->assertSee('Log Hotline Phone Report')
            ->assertSee('Caller Verbal Consent Obtained');
    }

    public function test_verbal_consent_is_strictly_required(): void
    {
        $response = $this->actingAs($this->authorizedStaff)
            ->post(route('admin.reports.phone.store'), [
                'purok_id' => $this->purok->id,
                'description' => 'Caller reports leaking water main on Main St.',
                // missing verbal_consent
            ]);

        $response->assertSessionHasErrors('verbal_consent');
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_authorized_staff_can_successfully_log_phone_report(): void
    {
        // Store audio first
        $audioFile = UploadedFile::fake()->create('call.webm', 450, 'audio/webm');
        $audio = app(VoiceService::class)->storeAudio($audioFile, ReportAudio::ORIGIN_STAFF, $this->authorizedStaff->id);

        $response = $this->actingAs($this->authorizedStaff)
            ->post(route('admin.reports.phone.store'), [
                'verbal_consent' => '1',
                'caller_name' => 'Maria Santos',
                'caller_phone' => '09181234567',
                'purok_id' => $this->purok->id,
                'landmark' => 'Near Chapel',
                'category_id' => $this->category->id,
                'urgency' => 'High',
                'description' => 'Caller reports uncollected garbage for two weeks.',
                'audio_token' => $audio->token,
            ]);

        $report = Report::first();
        $this->assertNotNull($report);
        $response->assertRedirect(route('admin.reports.show', $report));

        $this->assertEquals(Report::SOURCE_PHONE_LOG, $report->source);
        $this->assertEquals('Maria Santos', $report->reporter_name);
        $this->assertEquals('09181234567', $report->reporter_contact);
        $this->assertEquals('submitted', $report->status);
        $this->assertEquals($this->authorizedStaff->id, $report->consent_recorded_by);
        $this->assertNotNull($report->consent_at);

        $this->assertDatabaseHas('report_audios', [
            'id' => $audio->id,
            'report_id' => $report->id,
            'uploaded_by' => $this->authorizedStaff->id,
            'origin' => ReportAudio::ORIGIN_STAFF,
        ]);

        $this->assertDatabaseHas('status_logs', [
            'report_id' => $report->id,
            'to_status' => 'submitted',
        ]);
    }
}
