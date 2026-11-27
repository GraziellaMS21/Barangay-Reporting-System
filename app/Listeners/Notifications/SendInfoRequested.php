<?php

namespace App\Listeners\Notifications;

use App\Events\InfoRequested;
use App\Mail\InfoRequestedMail;
use App\Services\NotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendInfoRequested implements ShouldHandleEventsAfterCommit, ShouldQueue
{
    use InteractsWithQueue;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 1;

    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(InfoRequested $event): void
    {
        $report = $event->report->fresh();
        $infoRequest = $event->infoRequest->fresh();
        if (! $report || ! $infoRequest) {
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
            Mail::to($recipient)->send(new InfoRequestedMail($report, $infoRequest));
            $this->notificationService->log($report, 'info_requested', $recipient);
        } catch (Throwable $exception) {
            Log::warning('Info requested notification failed.', [
                'report_id' => $report->id,
                'exception' => $exception::class,
            ]);
            $this->notificationService->log($report, 'info_requested', $recipient, 'failed', $exception::class);

            throw $exception;
        }
    }
}
