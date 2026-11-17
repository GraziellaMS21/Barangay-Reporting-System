<?php

namespace App\Services;

use App\Events\ReportStatusChanged;
use App\Models\Report;
use App\Models\User;
use App\Support\ReportStatus;
use Illuminate\Support\Facades\DB;

class ReportStatusService
{
    /**
     * Transition a report to a new status, log to status_logs, and dispatch ReportStatusChanged.
     * This is the single place status_logs rows are written.
     */
    private const ALLOWED = [
        'submitted' => ['routed', 'needs_info', 'out_of_scope', 'referred', 'merged'],
        'under_review' => ['routed', 'needs_info', 'out_of_scope', 'referred', 'merged'],
        'reopened' => ['routed', 'needs_info', 'out_of_scope', 'referred', 'merged'],
        'routed' => ['in_progress', 'needs_info', 'referred'],
        'in_progress' => ['awaiting_confirmation', 'needs_info', 'referred'],
        'needs_info' => ['under_review', 'routed', 'in_progress'],
        'awaiting_confirmation' => ['resolved'],
        'emergency_flagged' => ['out_of_scope', 'under_review'],
    ];

    public function transition(
        Report $report,
        string $toStatus,
        ?string $note = null,
        ?User $user = null
    ): Report {
        if ($toStatus !== $report->status && !in_array($toStatus, self::ALLOWED[$report->status] ?? [], true)) {
            throw new \InvalidArgumentException("Transition from {$report->status} to {$toStatus} is not allowed.");
        }

        return DB::transaction(function () use ($report, $toStatus, $note, $user) {
            $fromStatus = $report->status;

            $report->status = $toStatus;

            if ($toStatus === ReportStatus::RESOLVED && ! $report->resolved_at) {
                $report->resolved_at = now();
            }

            if ($toStatus === ReportStatus::REOPENED) {
                $report->reopened_count = (int) $report->reopened_count + 1;
            }

            $report->save();

            $report->statusLogs()->create([
                'user_id' => $user?->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'note' => $note,
            ]);

            event(new ReportStatusChanged(
                $report,
                $fromStatus,
                $toStatus,
                $note,
                $user?->id
            ));

            return $report;
        });
    }
}
