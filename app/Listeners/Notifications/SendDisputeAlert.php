<?php

namespace App\Listeners\Notifications;

use App\Events\ResolutionConfirmed;
use App\Mail\ResidentDisputeMail;
use App\Models\Report;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\ReportStatus;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendDisputeAlert implements ShouldHandleEventsAfterCommit, ShouldQueue
{
    use InteractsWithQueue;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 1;

    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(ResolutionConfirmed $event): void
    {
        if ($event->confirmed) {
            return;
        }

        $report = $event->report->fresh(['assignee', 'category', 'purok']);
        if (! $report) {
            return;
        }

        $recipients = $this->resolveRecipients($report);
        if ($recipients->isEmpty()) {
            Log::warning('Resident dispute alert had no eligible staff or admin recipients.', [
                'report_id' => $report->id,
            ]);
            $this->notificationService->log(
                $report,
                'resident_disputed',
                '',
                'failed',
                'No eligible active assignee or administrator recipient found.'
            );

            return;
        }

        $disputeReason = is_string($event->disputeReason) && trim($event->disputeReason) !== ''
            ? trim($event->disputeReason)
            : $this->resolveDisputeReason($report);
        $lastException = null;

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new ResidentDisputeMail($report, $disputeReason, $event->feedback));
                $this->notificationService->log($report, 'resident_disputed', $recipient);
            } catch (Throwable $exception) {
                Log::warning('Resident dispute notification failed.', [
                    'report_id' => $report->id,
                    'exception' => $exception::class,
                ]);
                $this->notificationService->log(
                    $report,
                    'resident_disputed',
                    $recipient,
                    'failed',
                    $exception::class
                );
                $lastException = $exception;
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }
    }

    /** @return Collection<int, string> */
    private function resolveRecipients(Report $report): Collection
    {
        $assignee = $report->assignee;
        if ($assignee && $assignee->is_active) {
            $email = trim((string) $assignee->email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                return collect([$email]);
            }
        }

        return User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn (string $email): string => trim($email))
            ->filter(fn (string $email): bool => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values();
    }

    private function resolveDisputeReason(Report $report): ?string
    {
        $note = $report->statusLogs()
            ->where('to_status', ReportStatus::REOPENED)
            ->latest('id')
            ->value('note');

        if (! is_string($note) || trim($note) === '') {
            return null;
        }

        $prefix = 'Resident reopened report: ';

        return Str::startsWith($note, $prefix)
            ? trim(Str::after($note, $prefix))
            : trim($note);
    }
}
