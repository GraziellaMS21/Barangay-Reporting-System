<?php

namespace Tests\Feature;

use App\Events\ReportSubmitted;
use App\Events\ResolutionConfirmed;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\InfoRequest;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CategoryQuestionSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PurokSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ResidentReportingPhase9Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PurokSeeder::class, CategorySeeder::class, CategoryQuestionSeeder::class]);
    }

    public function test_resident_can_view_multi_step_form(): void
    {
        $response = $this->get(route('reports.create'));

        $response->assertOk();
        $response->assertSee('Drainage');
        $response->assertSee('Garbage');
        $response->assertSee('Road Damage');
        $response->assertSee('Data Privacy Notice');
        $response->assertSee('Purok');
    }

    public function test_resident_submits_report_with_category_specific_answers_and_extra_fields(): void
    {
        Event::fake([ReportSubmitted::class]);

        $category = Category::where('slug', 'drainage')->firstOrFail();
        $purok = Purok::firstOrFail();

        $response = $this->post(route('reports.store'), [
            'report_type' => 'non_emergency',
            'category_id' => $category->id,
            'description' => 'The drainage canal in front of the elementary school is heavily blocked.',
            'answers' => [
                'water_level' => 'Knee',
                'blocked_drain' => '1',
            ],
            'is_ongoing' => '1',
            'people_at_risk' => '1',
            'is_recurring' => '0',
            'is_anonymous' => '0',
            'preferred_contact' => 'phone',
            'reporter_name' => 'Maria Makiling',
            'reporter_contact' => '09171234567',
            'reporter_email' => 'maria@example.test',
            'purok_id' => $purok->id,
            'landmark' => 'In front of school gate',
            'lat' => 6.9214,
            'lng' => 122.0790,
            'consent' => '1',
            'has_consent_field' => '1',
        ]);

        $report = Report::latest()->firstOrFail();

        $response->assertRedirect(route('reports.success', $report->tracking_code));

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'report_type' => 'non_emergency',
            'category_id' => $category->id,
            'is_ongoing' => true,
            'people_at_risk' => true,
            'is_recurring' => false,
            'is_anonymous' => false,
            'preferred_contact' => 'email',
            'reporter_name' => 'Maria Makiling',
            'reporter_contact' => '09171234567',
            'status' => 'submitted',
        ]);

        $this->assertNotNull($report->consent_at);
        $this->assertEquals('Knee', $report->answers['water_level']);

        Event::assertDispatched(ReportSubmitted::class, function ($event) use ($report) {
            return $event->report->id === $report->id;
        });
    }

    public function test_anonymous_submission_clears_personal_details(): void
    {
        Event::fake([ReportSubmitted::class]);

        $purok = Purok::firstOrFail();

        $this->post(route('reports.store'), [
            'report_type' => 'non_emergency',
            'category_id' => Category::first()->id,
            'description' => 'Streetlight near basketball court has not turned on for three days.',
            'is_anonymous' => '1',
            'preferred_contact' => 'none',
            'reporter_name' => 'Hidden Resident',
            'reporter_contact' => '09170000000',
            'purok_id' => $purok->id,
            'consent' => '1',
            'has_consent_field' => '1',
        ]);

        $report = Report::latest()->firstOrFail();

        $this->assertTrue($report->is_anonymous);
        $this->assertNull($report->reporter_name);
        $this->assertNull($report->reporter_contact);
        $this->assertNull($report->reporter_email);
    }

    public function test_consent_is_required_when_submitting_from_form(): void
    {
        $purok = Purok::firstOrFail();

        $response = $this->post(route('reports.store'), [
            'category_id' => Category::first()->id,
            'description' => 'A large crack formed in the street after heavy rainfall.',
            'purok_id' => $purok->id,
            'has_consent_field' => '1',
            // consent omitted
        ]);

        $response->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_resident_can_respond_to_staff_info_request(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $report = Report::factory()->create([
            'status' => 'needs_info',
            'tracking_code' => 'BRG-TST001',
        ]);

        $infoRequest = InfoRequest::create([
            'report_id' => $report->id,
            'requested_by' => $staff->id,
            'question' => 'Which house number is nearest to the problem?',
        ]);

        $showResponse = $this->get(route('track.show', $report->tracking_code));
        $showResponse->assertOk();
        $showResponse->assertSee('Which house number is nearest to the problem?');

        $response = $this->post(route('track.info-response', $report->tracking_code), [
            'response' => 'It is right beside House #42 across the bakery.',
        ]);

        $response->assertRedirect(route('track.show', $report->tracking_code));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('info_requests', [
            'id' => $infoRequest->id,
            'response' => 'It is right beside House #42 across the bakery.',
        ]);

        $this->assertNotNull($infoRequest->fresh()->responded_at);
    }

    public function test_resident_can_confirm_resolution_and_leave_feedback(): void
    {
        Event::fake([ResolutionConfirmed::class]);

        $report = Report::factory()->create([
            'status' => 'awaiting_confirmation',
            'tracking_code' => 'BRG-TST002',
        ]);

        $showResponse = $this->get(route('track.show', $report->tracking_code));
        $showResponse->assertOk();
        $showResponse->assertSee('Please verify: Is the problem actually fixed?');

        $response = $this->post(route('track.confirm', $report->tracking_code), [
            'rating' => 5,
            'comment' => 'The team arrived on time and completely cleared the canal!',
        ]);

        $response->assertRedirect(route('track.show', $report->tracking_code));
        $response->assertSessionHas('status');

        $report->refresh();
        $this->assertEquals('resolved', $report->status);
        $this->assertNotNull($report->resolution_confirmed_at);

        $this->assertDatabaseHas('feedback', [
            'report_id' => $report->id,
            'rating' => 5,
            'comment' => 'The team arrived on time and completely cleared the canal!',
        ]);

        Event::assertDispatched(ResolutionConfirmed::class, function ($event) use ($report) {
            return $event->report->id === $report->id && $event->confirmed === true && $event->feedback instanceof Feedback;
        });
    }

    public function test_resident_can_reopen_unresolved_report(): void
    {
        Event::fake([ResolutionConfirmed::class]);

        $report = Report::factory()->create([
            'status' => 'awaiting_confirmation',
            'tracking_code' => 'BRG-TST003',
            'reopened_count' => 0,
        ]);

        $response = $this->post(route('track.reopen', $report->tracking_code), [
            'reason' => 'Water is still not draining properly when it rained yesterday.',
        ]);

        $response->assertRedirect(route('track.show', $report->tracking_code));
        $response->assertSessionHas('status');

        $report->refresh();
        $this->assertEquals('reopened', $report->status);
        $this->assertEquals(1, $report->reopened_count);
        $this->assertNull($report->resolution_confirmed_at);

        Event::assertDispatched(ResolutionConfirmed::class, function ($event) use ($report) {
            return $event->report->id === $report->id && $event->confirmed === false && $event->feedback === null;
        });
    }

    public function test_nearby_reports_api_returns_nearby_incidents(): void
    {
        $category = Category::firstOrFail();

        $nearbyReport = Report::factory()->create([
            'lat' => 6.9214,
            'lng' => 122.0790,
            'category_id' => $category->id,
            'status' => 'in_progress',
            'is_emergency' => false,
            'tracking_code' => 'BRG-NEARBY',
        ]);

        $response = $this->getJson('/api/nearby-reports?lat=6.9215&lng=122.0791&category=' . $category->slug);

        $response->assertOk();
        $response->assertJsonFragment([
            'tracking_code' => 'BRG-NEARBY',
            'category' => $category->name,
        ]);
    }
}

