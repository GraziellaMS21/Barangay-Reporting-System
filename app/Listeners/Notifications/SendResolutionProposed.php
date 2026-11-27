<?php

namespace App\Listeners\Notifications;

use App\Events\ResolutionProposed;
use App\Mail\ResolutionProposedMail;
use App\Services\NotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendResolutionProposed implements ShouldHandleEventsAfterCommit, ShouldQueue
{
    use InteractsWithQueue;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 1;

    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(ResolutionProposed $event): void
    {
        $report = $event->report->fresh();
        $proof = $event->proof->fresh();
        if (! $report || ! $proof) {
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
            Mail::to($recipient)->send(new ResolutionProposedMail($report, $proof));
            $this->notificationService->log($report, 'resolution_proposed', $recipient);
        } catch (Throwable $exception) {
            Log::warning('Resolution proposed notification failed.', [
                'report_id' => $report->id,
                'exception' => $exception::class,
            ]);
            $this->notificationService->log($report, 'resolution_proposed', $recipient, 'failed', $exception::class);

            throw $exception;
        }
    }
}
