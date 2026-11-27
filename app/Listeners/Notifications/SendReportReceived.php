<?php

namespace App\Listeners\Notifications;

use App\Events\ReportSubmitted;
use App\Mail\ReportReceivedMail;
use App\Services\NotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendReportReceived implements ShouldHandleEventsAfterCommit, ShouldQueue
{
    use InteractsWithQueue;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 1;

    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(ReportSubmitted $event): void
    {
        $report = $event->report->fresh(['category', 'purok']);
        if (! $report) {
            return;
        }

        if ($report->is_anonymous) {
            return;
        }

        $recipient = trim((string) $report->reporter_email);
        if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        try {
            Mail::to($recipient)->send(new ReportReceivedMail($report));
            $this->notificationService->log($report, 'report_received', $recipient);
        } catch (Throwable $exception) {
            Log::warning('Report received notification failed.', [
                'report_id' => $report->id,
                'exception' => $exception::class,
            ]);
            $this->notificationService->log($report, 'report_received', $recipient, 'failed', $exception::class);

            throw $exception;
        }
    }
}
