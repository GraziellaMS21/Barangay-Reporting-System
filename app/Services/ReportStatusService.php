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
        'submitted' => ['under_review', 'routed', 'needs_info', 'no_action', 'referred', 'merged', 'emergency_flagged'],
        'under_review' => ['routed', 'needs_info', 'no_action', 'referred', 'merged', 'emergency_flagged'],
        'reopened' => ['under_review', 'routed', 'in_progress', 'needs_info', 'no_action', 'referred', 'merged', 'emergency_flagged'],
        'routed' => ['in_progress', 'needs_info', 'referred', 'no_action', 'awaiting_confirmation', 'resolved', 'emergency_flagged'],
        'in_progress' => ['in_progress', 'needs_info', 'referred', 'no_action', 'awaiting_confirmation', 'resolved', 'emergency_flagged'],
        'needs_info' => ['under_review', 'routed', 'in_progress', 'needs_info', 'referred', 'no_action', 'awaiting_confirmation', 'resolved', 'emergency_flagged'],
        'awaiting_confirmation' => ['resolved', 'no_action', 'in_progress', 'reopened', 'emergency_flagged'],
        'emergency_flagged' => ['no_action', 'under_review'],
        // Referred reports stay open while the agency works; barangay staff may
        // re-route internally, take no action, or resolve after the agency
        // reports the issue fixed.
        'referred' => ['routed', 'in_progress', 'needs_info', 'no_action', 'merged', 'resolved'],
        'resolved' => ['in_progress', 'reopened'],
        'no_action' => ['in_progress', 'reopened'],
    ];

    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, self::ALLOWED[$fromStatus] ?? [], true);
    }

    public function transition(
        Report $report,
        string $toStatus,
        ?string $note = null,
        ?User $user = null,
        array $extraAttributes = []
    ): Report {
        if (! $this->canTransition($report->status, $toStatus)) {
            throw new \InvalidArgumentException("Transition from {$report->status} to {$toStatus} is not allowed.");
        }

        if ($toStatus === ReportStatus::NO_ACTION) {
            $reason = $extraAttributes['no_action_reason'] ?? null;
            if (! in_array($reason, ReportStatus::noActionReasons(), true)) {
                throw new \InvalidArgumentException('A valid no_action_reason is required when transitioning to no_action.');
            }
            if (empty($note) || trim($note) === '') {
                throw new \InvalidArgumentException('A note explaining the reason is required when transitioning to no_action.');
            }
        }

        return DB::transaction(function () use ($report, $toStatus, $note, $user, $extraAttributes) {
            $fromStatus = $report->status;

            $report->status = $toStatus;

            if ($toStatus === ReportStatus::NO_ACTION) {
                $report->no_action_reason = $extraAttributes['no_action_reason'];
            } elseif ($report->no_action_reason !== null) {
                $report->no_action_reason = null;
            }

            if ($toStatus === ReportStatus::RESOLVED && ! $report->resolved_at) {
                $report->resolved_at = now();
            }

            if ($toStatus === ReportStatus::REOPENED) {
                $report->reopened_count = (int) $report->reopened_count + 1;
            }

            $report->save();

            $logData = array_merge([
                'user_id' => $user?->id,
                'office_id' => $extraAttributes['office_id'] ?? $report->office_id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'no_action_reason' => $toStatus === ReportStatus::NO_ACTION ? $extraAttributes['no_action_reason'] : null,
                'note' => $note,
            ], $extraAttributes);

            $report->statusLogs()->create($logData);

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
