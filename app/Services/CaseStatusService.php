<?php

namespace App\Services;

use App\Models\BarangayCase;
use App\Models\CaseStatusLog;
use App\Models\User;
use App\Support\CaseStatus;
use LogicException;

class CaseStatusService
{
    public function __construct(
        protected AuditLogger $audit,
        protected FiscalYearGuard $fiscalYearGuard
    ) {}

    /**
     * Allowed transition table (Draft, pending barangay confirmation).
     */
    protected const ALLOWED_TRANSITIONS = [
        CaseStatus::FILED => [CaseStatus::IN_MEDIATION, CaseStatus::SETTLED, CaseStatus::DISMISSED, CaseStatus::WITHDRAWN],
        CaseStatus::IN_MEDIATION => [CaseStatus::SETTLED, CaseStatus::UNSETTLED, CaseStatus::DISMISSED, CaseStatus::WITHDRAWN],
        CaseStatus::UNSETTLED => [CaseStatus::ESCALATED, CaseStatus::IN_MEDIATION, CaseStatus::DISMISSED, CaseStatus::WITHDRAWN],
        CaseStatus::ESCALATED => [CaseStatus::IN_MEDIATION],
        CaseStatus::SETTLED => [CaseStatus::IN_MEDIATION],
        CaseStatus::DISMISSED => [CaseStatus::IN_MEDIATION],
        CaseStatus::WITHDRAWN => [CaseStatus::IN_MEDIATION],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::ALLOWED_TRANSITIONS[$from] ?? [], true);
    }

    /**
     * STUB: WS3 completes full case status workflow.
     */
    public function transition(BarangayCase $case, string $to, string $note, ?User $by = null): BarangayCase
    {
        $this->fiscalYearGuard->assertWritable($case);

        $from = $case->status;
        if (! $this->canTransition($from, $to)) {
            throw new LogicException("Cannot transition case from {$from} to {$to}.");
        }

        $case->update([
            'status' => $to,
            'settled_on' => ($to === CaseStatus::SETTLED) ? now()->toDateString() : $case->settled_on,
        ]);

        CaseStatusLog::create([
            'case_id' => $case->id,
            'user_id' => $by?->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'created_at' => now(),
        ]);

        $this->audit->log($by, 'case.status_changed', $case, ['status' => $from], ['status' => $to, 'note' => $note]);

        return $case->fresh();
    }
}
