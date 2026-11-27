<?php

namespace Tests\Feature;

use App\Events\InfoRequested;
use App\Events\ReportSubmitted;
use App\Events\ResolutionConfirmed;
use App\Events\ResolutionProposed;
use App\Listeners\Engine\ProcessNewReport;
use App\Listeners\Notifications\SendDisputeAlert;
use App\Listeners\Notifications\SendInfoRequested;
use App\Listeners\Notifications\SendReportReceived;
use App\Listeners\Notifications\SendResolutionProposed;
use App\Mail\InfoRequestedMail;
use App\Mail\ReportReceivedMail;
use App\Mail\ResidentDisputeMail;
use App\Mail\ResolutionProposedMail;
use App\Models\Category;
use App\Models\InfoRequest;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportProofPhoto;
use App\Models\ResolutionProof;
use App\Models\StatusLog;
use App\Models\User;
use App\Support\ReportStatus;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Category $category;

    private Purok $purok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
            'email' => 'staff@calarian.test',
        ]);
        $this->staff->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
        ]);

        $this->purok = Purok::factory()->create(['name' => 'Purok 1']);
        $this->category = Category::create([
            'name' => 'Streetlight',
            'slug' => 'streetlight',
            'is_active' => true,
        ]);
    }

    public function test_listeners_are_registered_including_process_new_report(): void
    {
        Event::fake();

        Event::assertListening(ReportSubmitted::class, ProcessNewReport::class);
        Event::assertListening(ReportSubmitted::class, SendReportReceived::class);
        Event::assertListening(InfoRequested::class, SendInfoRequested::class);
        Event::assertListening(ResolutionProposed::class, SendResolutionProposed::class);
        Event::assertListening(ResolutionConfirmed::class, SendDisputeAlert::class);
    }

    public function test_report_received_email_is_queued_for_eligible_resident_and_logged(): void
    {
        Queue::fake();

        $this->post(route('reports.store'), [
            'category_id' => $this->category->id,
            'purok_id' => $this->purok->id,
            'landmark' => 'Near Chapel',
            'description' => 'Streetlight is out near the chapel.',
            'reporter_name' => 'Juan Dela Cruz',
            'reporter_email' => 'juan@example.com',
            'is_anonymous' => false,
            'privacy_consent' => '1',
        ])->assertRedirect();

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job): bool {
            return $job->class === SendReportReceived::class;
        });

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job): bool {
            return $job->class === ProcessNewReport::class;
        });

        Mail::fake();
        $report = Report::firstOrFail();
        app(SendReportReceived::class)->handle(new ReportSubmitted($report));

        Mail::assertSent(ReportReceivedMail::class, function (ReportReceivedMail $mail) use ($report): bool {
            return $mail->hasTo('juan@example.com')
                && $mail->report->is($report);
        });

        $this->assertDatabaseHas('notification_logs', [
            'report_id' => $report->id,
            'type' => 'report_received',
            'recipient' => 'juan@example.com',
            'status' => 'sent',
        ]);
    }

    public function test_anonymous_and_invalid_or_missing_email_reports_are_skipped_without_failed_logs(): void
    {
        Mail::fake();

        $anonymous = Report::factory()->create([
            'is_anonymous' => true,
            'reporter_email' => 'hidden@example.com',
        ]);
        $missingEmail = Report::factory()->create([
            'is_anonymous' => false,
            'reporter_email' => null,
        ]);
        $invalidEmail = Report::factory()->create([
            'is_anonymous' => false,
            'reporter_email' => 'not-an-email',
        ]);

        $listener = app(SendReportReceived::class);
        $listener->handle(new ReportSubmitted($anonymous));
        $listener->handle(new ReportSubmitted($missingEmail));
        $listener->handle(new ReportSubmitted($invalidEmail));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('notification_logs', 0);
    }

    public function test_info_requested_email_is_queued_and_sent_with_question(): void
    {
        Queue::fake();

        $report = Report::factory()->create([
            'status' => ReportStatus::UNDER_REVIEW,
            'is_anonymous' => false,
            'reporter_email' => 'resident@example.com',
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.info-requests.store', $report), [
                'question' => 'Which exact house number is nearest to the leak?',
            ])
            ->assertRedirect();

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job): bool {
            return $job->class === SendInfoRequested::class;
        });

        Mail::fake();
        $infoRequest = InfoRequest::where('report_id', $report->id)->firstOrFail();
        app(SendInfoRequested::class)->handle(new InfoRequested($report, $infoRequest));

        Mail::assertSent(InfoRequestedMail::class, function (InfoRequestedMail $mail): bool {
            return $mail->hasTo('resident@example.com')
                && $mail->infoRequest->question === 'Which exact house number is nearest to the leak?';
        });

        $this->assertDatabaseHas('notification_logs', [
            'report_id' => $report->id,
            'type' => 'info_requested',
            'recipient' => 'resident@example.com',
            'status' => 'sent',
        ]);
    }

    public function test_resolution_proposed_email_is_queued_and_sent_with_proof_note(): void
    {
        Queue::fake();
        Storage::fake('public');

        $report = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
            'is_anonymous' => false,
            'reporter_email' => 'resident@example.com',
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.resolve', $report), [
                'note' => 'Replaced busted lamp and tested wiring.',
                'proof_photos' => [UploadedFile::fake()->image('fixed.jpg')],
            ])
            ->assertRedirect();

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job): bool {
            return $job->class === SendResolutionProposed::class;
        });

        Mail::fake();
        $proof = ResolutionProof::where('report_id', $report->id)->firstOrFail();
        app(SendResolutionProposed::class)->handle(new ResolutionProposed($report, $proof));

        Mail::assertSent(ResolutionProposedMail::class, function (ResolutionProposedMail $mail): bool {
            return $mail->hasTo('resident@example.com')
                && $mail->proof->note === 'Replaced busted lamp and tested wiring.';
        });

        $this->assertDatabaseHas('notification_logs', [
            'report_id' => $report->id,
            'type' => 'resolution_proposed',
            'recipient' => 'resident@example.com',
            'status' => 'sent',
        ]);
    }

    public function test_resident_dispute_sends_email_to_active_assigned_staff(): void
    {
        Mail::fake();

        $report = Report::factory()->create([
            'status' => ReportStatus::AWAITING_CONFIRMATION,
            'assigned_to' => $this->staff->id,
            'category_id' => $this->category->id,
            'purok_id' => $this->purok->id,
        ]);
        ReportProofPhoto::create([
            'report_id' => $report->id,
            'path' => 'proof_photos/test.jpg',
            'uploaded_by' => $this->staff->id,
        ]);

        $this->post(route('track.reopen', $report->tracking_code), [
            'reason' => 'The streetlight is still blinking every night.',
        ])->assertRedirect();

        Mail::assertSent(ResidentDisputeMail::class, function (ResidentDisputeMail $mail): bool {
            return $mail->hasTo('staff@calarian.test')
                && $mail->disputeReason === 'The streetlight is still blinking every night.';
        });

        $this->assertDatabaseHas('notification_logs', [
            'report_id' => $report->id,
            'type' => 'resident_disputed',
            'recipient' => 'staff@calarian.test',
            'status' => 'sent',
        ]);
    }

    public function test_inactive_or_missing_assignee_falls_back_to_all_active_admins(): void
    {
        Mail::fake();

        $inactiveStaff = User::factory()->create([
            'role' => 'staff',
            'is_active' => false,
            'email' => 'inactive@calarian.test',
        ]);
        $adminOne = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email' => 'captain@calarian.test',
        ]);
        $adminTwo = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email' => 'secretary@calarian.test',
        ]);
        User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
            'email' => 'former-admin@calarian.test',
        ]);

        $report = Report::factory()->create([
            'status' => ReportStatus::REOPENED,
            'assigned_to' => $inactiveStaff->id,
        ]);
        StatusLog::create([
            'report_id' => $report->id,
            'from_status' => ReportStatus::AWAITING_CONFIRMATION,
            'to_status' => ReportStatus::REOPENED,
            'note' => 'Resident reopened report: Drainage still clogged.',
        ]);

        app(SendDisputeAlert::class)->handle(new ResolutionConfirmed($report, false, null));

        Mail::assertSent(ResidentDisputeMail::class, 2);
        Mail::assertSent(ResidentDisputeMail::class, fn (ResidentDisputeMail $m): bool => $m->hasTo($adminOne->email));
        Mail::assertSent(ResidentDisputeMail::class, fn (ResidentDisputeMail $m): bool => $m->hasTo($adminTwo->email));
        Mail::assertNotSent(ResidentDisputeMail::class, fn (ResidentDisputeMail $m): bool => $m->hasTo('inactive@calarian.test'));
        Mail::assertNotSent(ResidentDisputeMail::class, fn (ResidentDisputeMail $m): bool => $m->hasTo('former-admin@calarian.test'));

        $this->assertDatabaseHas('notification_logs', [
            'report_id' => $report->id,
            'type' => 'resident_disputed',
            'recipient' => 'captain@calarian.test',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'report_id' => $report->id,
            'type' => 'resident_disputed',
            'recipient' => 'secretary@calarian.test',
            'status' => 'sent',
        ]);
    }

    public function test_confirmed_resolution_does_not_send_dispute_alert(): void
    {
        Mail::fake();

        $report = Report::factory()->create([
            'status' => ReportStatus::AWAITING_CONFIRMATION,
            'assigned_to' => $this->staff->id,
        ]);

        $this->post(route('track.confirm', $report->tracking_code), [
            'rating' => 5,
            'comment' => 'All fixed!',
        ])->assertRedirect();

        Mail::assertNotSent(ResidentDisputeMail::class);
        $this->assertDatabaseMissing('notification_logs', [
            'report_id' => $report->id,
            'type' => 'resident_disputed',
        ]);
    }

    public function test_missing_dispute_reason_is_handled_safely(): void
    {
        Mail::fake();

        $report = Report::factory()->create([
            'status' => ReportStatus::REOPENED,
            'assigned_to' => $this->staff->id,
        ]);

        app(SendDisputeAlert::class)->handle(new ResolutionConfirmed($report, false, null));

        Mail::assertSent(ResidentDisputeMail::class, function (ResidentDisputeMail $mail): bool {
            return $mail->hasTo('staff@calarian.test')
                && $mail->disputeReason === null;
        });
    }

    public function test_failed_mail_delivery_records_failed_notification_log_and_rethrows(): void
    {
        $report = Report::factory()->create([
            'is_anonymous' => false,
            'reporter_email' => 'resident@example.com',
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->with('resident@example.com')
            ->andThrow(new RuntimeException('SMTP connection refused'));

        try {
            app(SendReportReceived::class)->handle(new ReportSubmitted($report));
            $this->fail('Expected RuntimeException to be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('SMTP connection refused', $exception->getMessage());
        }

        $this->assertDatabaseHas('notification_logs', [
            'report_id' => $report->id,
            'type' => 'report_received',
            'recipient' => 'resident@example.com',
            'status' => 'failed',
            'error' => RuntimeException::class,
        ]);
    }

    public function test_unassigned_report_falls_back_to_eligible_active_administrators(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email' => 'admin-fallback@calarian.test',
        ]);
        User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
            'email' => 'inactive-admin@calarian.test',
        ]);

        $report = Report::factory()->create([
            'status' => ReportStatus::REOPENED,
            'assigned_to' => null,
        ]);

        app(SendDisputeAlert::class)->handle(new ResolutionConfirmed($report, false, null, 'Unassigned report still broken.'));

        Mail::assertSent(ResidentDisputeMail::class, 1);
        Mail::assertSent(ResidentDisputeMail::class, function (ResidentDisputeMail $mail) use ($admin): bool {
            return $mail->hasTo($admin->email)
                && $mail->disputeReason === 'Unassigned report still broken.';
        });

        $this->assertDatabaseHas('notification_logs', [
            'report_id' => $report->id,
            'type' => 'resident_disputed',
            'recipient' => 'admin-fallback@calarian.test',
            'status' => 'sent',
        ]);
    }

    public function test_no_eligible_assignee_or_admin_records_failed_notification_log(): void
    {
        Mail::fake();

        $report = Report::factory()->create([
            'status' => ReportStatus::REOPENED,
            'assigned_to' => null,
        ]);

        app(SendDisputeAlert::class)->handle(new ResolutionConfirmed($report, false, null, 'No eligible recipients exist.'));

        Mail::assertNothingSent();
        $this->assertDatabaseHas('notification_logs', [
            'report_id' => $report->id,
            'type' => 'resident_disputed',
            'recipient' => '',
            'status' => 'failed',
            'error' => 'No eligible active assignee or administrator recipient found.',
        ]);
    }

    public function test_partial_failure_with_multiple_fallback_admins_records_accurate_per_recipient_logs(): void
    {
        $adminOne = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email' => 'admin1@calarian.test',
        ]);
        $adminTwo = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email' => 'admin2@calarian.test',
        ]);

        $report = Report::factory()->create([
            'status' => ReportStatus::REOPENED,
            'assigned_to' => null,
        ]);

        $okPendingMail = \Mockery::mock(PendingMail::class);
        $okPendingMail->shouldReceive('send')->once()->andReturnNull();

        $failPendingMail = \Mockery::mock(PendingMail::class);
        $failPendingMail->shouldReceive('send')->once()->andThrow(new RuntimeException('smtp://secret:pass@mail.local failed'));

        Mail::shouldReceive('to')->once()->with($adminOne->email)->andReturn($okPendingMail);
        Mail::shouldReceive('to')->once()->with($adminTwo->email)->andReturn($failPendingMail);

        try {
            app(SendDisputeAlert::class)->handle(new ResolutionConfirmed($report, false, null, 'Partial delivery test.'));
            $this->fail('Expected RuntimeException to be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('smtp://secret:pass@mail.local failed', $exception->getMessage());
        }

        $this->assertDatabaseHas('notification_logs', [
            'report_id' => $report->id,
            'type' => 'resident_disputed',
            'recipient' => 'admin1@calarian.test',
            'status' => 'sent',
            'error' => null,
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'report_id' => $report->id,
            'type' => 'resident_disputed',
            'recipient' => 'admin2@calarian.test',
            'status' => 'failed',
            'error' => RuntimeException::class,
        ]);
    }

    public function test_info_requested_listener_skips_anonymous_and_missing_email_reports(): void
    {
        Mail::fake();

        $anonymous = Report::factory()->create([
            'is_anonymous' => true,
            'reporter_email' => 'hidden@example.com',
        ]);
        $missingEmail = Report::factory()->create([
            'is_anonymous' => false,
            'reporter_email' => null,
        ]);

        $infoOne = InfoRequest::create(['report_id' => $anonymous->id, 'requested_by' => $this->staff->id, 'question' => 'Details?']);
        $infoTwo = InfoRequest::create(['report_id' => $missingEmail->id, 'requested_by' => $this->staff->id, 'question' => 'Details?']);

        $listener = app(SendInfoRequested::class);
        $listener->handle(new InfoRequested($anonymous, $infoOne));
        $listener->handle(new InfoRequested($missingEmail, $infoTwo));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('notification_logs', 0);
    }

    public function test_resolution_proposed_listener_skips_anonymous_and_missing_email_reports(): void
    {
        Mail::fake();

        $anonymous = Report::factory()->create([
            'is_anonymous' => true,
            'reporter_email' => 'hidden@example.com',
        ]);
        $missingEmail = Report::factory()->create([
            'is_anonymous' => false,
            'reporter_email' => null,
        ]);

        $proofOne = ResolutionProof::create(['report_id' => $anonymous->id, 'user_id' => $this->staff->id, 'note' => 'Fixed']);
        $proofTwo = ResolutionProof::create(['report_id' => $missingEmail->id, 'user_id' => $this->staff->id, 'note' => 'Fixed']);

        $listener = app(SendResolutionProposed::class);
        $listener->handle(new ResolutionProposed($anonymous, $proofOne));
        $listener->handle(new ResolutionProposed($missingEmail, $proofTwo));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('notification_logs', 0);
    }

    public function test_two_dispute_events_retain_own_reasons_even_when_processed_out_of_order(): void
    {
        Mail::fake();

        $report = Report::factory()->create([
            'status' => ReportStatus::REOPENED,
            'assigned_to' => $this->staff->id,
        ]);

        StatusLog::create([
            'report_id' => $report->id,
            'from_status' => ReportStatus::AWAITING_CONFIRMATION,
            'to_status' => ReportStatus::REOPENED,
            'note' => 'Resident reopened report: First dispute reason.',
        ]);
        $firstEvent = new ResolutionConfirmed($report, false, null, 'First dispute reason.');

        StatusLog::create([
            'report_id' => $report->id,
            'from_status' => ReportStatus::AWAITING_CONFIRMATION,
            'to_status' => ReportStatus::REOPENED,
            'note' => 'Resident reopened report: Second dispute reason.',
        ]);
        $secondEvent = new ResolutionConfirmed($report, false, null, 'Second dispute reason.');

        // Process second event first, then first event out of order
        $listener = app(SendDisputeAlert::class);
        $listener->handle($secondEvent);
        $listener->handle($firstEvent);

        Mail::assertSent(ResidentDisputeMail::class, 2);
        Mail::assertSent(ResidentDisputeMail::class, fn (ResidentDisputeMail $m): bool => $m->disputeReason === 'Second dispute reason.');
        Mail::assertSent(ResidentDisputeMail::class, fn (ResidentDisputeMail $m): bool => $m->disputeReason === 'First dispute reason.');
    }
}
