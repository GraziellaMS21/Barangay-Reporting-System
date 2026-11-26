<?php

namespace Tests\Feature;

use App\Events\ReportStatusChanged;
use App\Events\ReportSubmitted;
use App\Events\ResolutionConfirmed;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\InfoRequest;
use App\Models\Purok;
use App\Models\Report;
use App\Models\StatusLog;
use App\Models\User;
use App\Support\ReportStatus;
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
                'water_blocked' => 'Yes',
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
        $this->assertEquals('Yes', $report->answers['water_blocked']);

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
        Event::fake([ReportStatusChanged::class]);

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

        $report->refresh();
        $this->assertEquals(ReportStatus::UNDER_REVIEW, $report->status);

        Event::assertDispatched(ReportStatusChanged::class, function ($event) use ($report) {
            return $event->report->id === $report->id
                && $event->from === ReportStatus::NEEDS_INFO
                && $event->to === ReportStatus::UNDER_REVIEW;
        });
    }

    public function test_resident_response_resumes_to_previous_active_status(): void
    {
        Event::fake([ReportStatusChanged::class]);

        $staff = User::factory()->create(['role' => 'staff']);
        $report = Report::factory()->create([
            'status' => 'needs_info',
            'tracking_code' => 'BRG-TST-RESUME',
        ]);

        StatusLog::create([
            'report_id' => $report->id,
            'from_status' => ReportStatus::IN_PROGRESS,
            'to_status' => ReportStatus::NEEDS_INFO,
            'note' => 'Clarification requested by staff',
        ]);

        InfoRequest::create([
            'report_id' => $report->id,
            'requested_by' => $staff->id,
            'question' => 'Is the water line still leaking?',
        ]);

        $response = $this->post(route('track.info-response', $report->tracking_code), [
            'response' => 'Yes, still leaking heavily near the corner meter.',
        ]);

        $response->assertRedirect(route('track.show', $report->tracking_code));
        $report->refresh();
        $this->assertEquals(ReportStatus::IN_PROGRESS, $report->status);

        Event::assertDispatched(ReportStatusChanged::class, function ($event) use ($report) {
            return $event->report->id === $report->id
                && $event->from === ReportStatus::NEEDS_INFO
                && $event->to === ReportStatus::IN_PROGRESS;
        });
    }

    public function test_resident_response_does_not_resume_if_other_info_requests_remain_pending(): void
    {
        Event::fake([ReportStatusChanged::class]);

        $staff = User::factory()->create(['role' => 'staff']);
        $report = Report::factory()->create([
            'status' => 'needs_info',
            'tracking_code' => 'BRG-TST-MULTI',
        ]);

        StatusLog::create([
            'report_id' => $report->id,
            'from_status' => ReportStatus::IN_PROGRESS,
            'to_status' => ReportStatus::NEEDS_INFO,
            'note' => 'Need additional details',
        ]);

        $firstRequest = InfoRequest::forceCreate([
            'report_id' => $report->id,
            'requested_by' => $staff->id,
            'question' => 'Question 1: What is the house number?',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $secondRequest = InfoRequest::forceCreate([
            'report_id' => $report->id,
            'requested_by' => $staff->id,
            'question' => 'Question 2: Who owns the property?',
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        // Responding to the latest one (secondRequest)
        $response = $this->post(route('track.info-response', $report->tracking_code), [
            'response' => 'The property belongs to Mr. Santos.',
        ]);

        $response->assertRedirect(route('track.show', $report->tracking_code));
        $report->refresh();

        // Still needs_info because firstRequest is still unfulfilled
        $this->assertEquals(ReportStatus::NEEDS_INFO, $report->status);
        $this->assertNotNull($secondRequest->fresh()->responded_at);
        $this->assertNull($firstRequest->fresh()->responded_at);
        Event::assertNotDispatched(ReportStatusChanged::class);

        // Now respond to the remaining one
        $response2 = $this->post(route('track.info-response', $report->tracking_code), [
            'response' => 'House number is #15-B.',
        ]);

        $response2->assertRedirect(route('track.show', $report->tracking_code));
        $report->refresh();
        $this->assertEquals(ReportStatus::IN_PROGRESS, $report->status);
        $this->assertNotNull($firstRequest->fresh()->responded_at);

        Event::assertDispatched(ReportStatusChanged::class, function ($event) use ($report) {
            return $event->report->id === $report->id
                && $event->from === ReportStatus::NEEDS_INFO
                && $event->to === ReportStatus::IN_PROGRESS;
        });
    }

    public function test_resident_can_confirm_resolution_and_leave_feedback(): void
    {
        Event::fake([ResolutionConfirmed::class, ReportStatusChanged::class]);

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
        $this->assertNotNull($report->resolved_at);
        $this->assertNotNull($report->resolution_confirmed_at);

        $this->assertDatabaseHas('feedback', [
            'report_id' => $report->id,
            'rating' => 5,
            'comment' => 'The team arrived on time and completely cleared the canal!',
        ]);

        Event::assertDispatched(ReportStatusChanged::class, function ($event) use ($report) {
            return $event->report->id === $report->id
                && $event->from === ReportStatus::AWAITING_CONFIRMATION
                && $event->to === ReportStatus::RESOLVED;
        });

        Event::assertDispatched(ResolutionConfirmed::class, function ($event) use ($report) {
            return $event->report->id === $report->id && $event->confirmed === true && $event->feedback instanceof Feedback;
        });
    }

    public function test_confirm_resolution_rejects_invalid_source_status(): void
    {
        Event::fake([ResolutionConfirmed::class, ReportStatusChanged::class]);

        $report = Report::factory()->create([
            'status' => ReportStatus::SUBMITTED,
            'tracking_code' => 'BRG-TST-INVALID-CONFIRM',
        ]);

        $response = $this->post(route('track.confirm', $report->tracking_code), [
            'rating' => 5,
        ]);

        $response->assertRedirect(route('track.show', $report->tracking_code));
        $response->assertSessionHasErrors(['status']);

        $report->refresh();
        $this->assertEquals(ReportStatus::SUBMITTED, $report->status);
        $this->assertNull($report->resolution_confirmed_at);
        $this->assertDatabaseMissing('feedback', ['report_id' => $report->id]);

        Event::assertNotDispatched(ResolutionConfirmed::class);
        Event::assertNotDispatched(ReportStatusChanged::class);
    }

    public function test_resident_can_reopen_unresolved_report(): void
    {
        Event::fake([ResolutionConfirmed::class, ReportStatusChanged::class]);

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

        Event::assertDispatched(ReportStatusChanged::class, function ($event) use ($report) {
            return $event->report->id === $report->id
                && $event->from === ReportStatus::AWAITING_CONFIRMATION
                && $event->to === ReportStatus::REOPENED;
        });

        Event::assertDispatched(ResolutionConfirmed::class, function ($event) use ($report) {
            return $event->report->id === $report->id && $event->confirmed === false && $event->feedback === null;
        });
    }

    public function test_resident_can_reopen_from_resolved_status(): void
    {
        Event::fake([ResolutionConfirmed::class, ReportStatusChanged::class]);

        $report = Report::factory()->create([
            'status' => ReportStatus::RESOLVED,
            'tracking_code' => 'BRG-TST-REOPEN-RESOLVED',
            'reopened_count' => 1,
            'resolution_confirmed_at' => now(),
        ]);

        $response = $this->post(route('track.reopen', $report->tracking_code), [
            'reason' => 'The clog came back after two days and water is spilling again.',
        ]);

        $response->assertRedirect(route('track.show', $report->tracking_code));
        $response->assertSessionHas('status');

        $report->refresh();
        $this->assertEquals(ReportStatus::REOPENED, $report->status);
        $this->assertEquals(2, $report->reopened_count);
        $this->assertNull($report->resolution_confirmed_at);

        Event::assertDispatched(ReportStatusChanged::class, function ($event) use ($report) {
            return $event->report->id === $report->id
                && $event->from === ReportStatus::RESOLVED
                && $event->to === ReportStatus::REOPENED;
        });

        Event::assertDispatched(ResolutionConfirmed::class, function ($event) use ($report) {
            return $event->report->id === $report->id && $event->confirmed === false && $event->feedback === null;
        });
    }

    public function test_reopen_rejects_invalid_source_status(): void
    {
        Event::fake([ResolutionConfirmed::class, ReportStatusChanged::class]);

        $report = Report::factory()->create([
            'status' => ReportStatus::SUBMITTED,
            'tracking_code' => 'BRG-TST-INVALID-REOPEN',
            'reopened_count' => 0,
        ]);

        $response = $this->post(route('track.reopen', $report->tracking_code), [
            'reason' => 'Attempting to reopen an unreviewed submitted report.',
        ]);

        $response->assertRedirect(route('track.show', $report->tracking_code));
        $response->assertSessionHasErrors(['status']);

        $report->refresh();
        $this->assertEquals(ReportStatus::SUBMITTED, $report->status);
        $this->assertEquals(0, $report->reopened_count);

        Event::assertNotDispatched(ResolutionConfirmed::class);
        Event::assertNotDispatched(ReportStatusChanged::class);
    }

    public function test_resident_reopen_requires_minimum_ten_characters(): void
    {
        $report = Report::factory()->create([
            'status' => 'awaiting_confirmation',
            'tracking_code' => 'BRG-TST004',
        ]);

        $response = $this->from(route('track.show', $report->tracking_code))
            ->post(route('track.reopen', $report->tracking_code), [
                'reason' => 'Too short',
            ]);

        $response->assertRedirect(route('track.show', $report->tracking_code));
        $response->assertSessionHasErrors(['reason']);

        $report->refresh();
        $this->assertEquals('awaiting_confirmation', $report->status);
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

        $response = $this->getJson('/api/nearby-reports?lat=6.9215&lng=122.0791&category='.$category->slug);

        $response->assertOk();
        $response->assertJsonFragment([
            'tracking_code' => 'BRG-NEARBY',
            'category' => $category->name,
        ]);
    }
}
