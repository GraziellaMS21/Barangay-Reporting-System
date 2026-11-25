<?php

namespace App\Services;

use App\Models\BarangayCase;
use App\Models\Hearing;
use App\Models\Summons;
use App\Support\CaseStatus;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class KatarungangPambarangayService
{
    public function determineNextStep(BarangayCase $case): array
    {
        return $this->getWorkflowState($case);
    }

    /**
     * Resolve the 6 milestones and the single current Next Step for a BarangayCase.
     *
     * @return array<string, mixed>
     */
    public function getWorkflowState(BarangayCase $case): array
    {
        $activeHearings = $case->hearings()
            ->whereNull('superseded_at')
            ->with('summons')
            ->orderBy('sequence_no')
            ->orderBy('id')
            ->get();

        /** @var Hearing|null $latestHearing */
        $latestHearing = $activeHearings->last();
        /** @var Summons|null $activeSummons */
        $activeSummons = $latestHearing?->summons
            ?? $case->activeSummons()->latest('sequence_no')->latest('id')->first();

        $currentAttemptNo = $latestHearing?->sequence_no ?? ($activeSummons?->sequence_no ?? 1);
        $nextAttemptNo = ((int) $case->hearings()->max('sequence_no')) + 1;
        if ($nextAttemptNo <= 1 && $activeSummons) {
            $nextAttemptNo = ((int) $case->summons()->max('sequence_no')) + 1;
        }

        // 1. Signed Complaint
        $complaintSigned = ! empty($case->complaint_signed_received_at);
        $complaintPrintedDate = $case->complaint_printed_at?->format('M d, Y');

        // 2. Hearing & Summons (latest active attempt)
        $hearingAndSummonsIssued = $latestHearing !== null && $activeSummons !== null;

        // 3. Service on latest active attempt
        $serviceRecorded = $activeSummons?->isServiceRecorded() ?? false;
        $serviceSkipped = $activeSummons?->isServiceSkipped() ?? false;
        $serviceSatisfied = $serviceRecorded || $serviceSkipped;
        $serviceNotServed = $activeSummons?->service_method === 'not_served';

        // 4 & 5. Latest hearing outcome & resolution
        $latestHearingOutcomeRecorded = $latestHearing !== null
            && in_array($latestHearing->status, ['held', 'settled', 'unsettled', 'reset'], true);
        $isAbsentOrNotServed = $serviceNotServed
            || in_array($latestHearing?->status, ['reset'], true)
            || ($latestHearingOutcomeRecorded && (
                $latestHearing?->complainant_attendance === 'failed_to_appear'
                || $latestHearing?->respondent_attendance === 'failed_to_appear'
            ));

        $isSettledOutcome = $case->mediation_outcome === 'settled'
            || $latestHearing?->status === 'settled'
            || $case->status === CaseStatus::SETTLED;
        $settlementSigned = $isSettledOutcome && ! empty($case->settled_on);

        $isEndorsedOrCfa = $case->status === CaseStatus::IN_CONCILIATION
            || $case->status === CaseStatus::ESCALATED
            || ! empty($case->endorsed_to_pangkat_on)
            || ! empty($case->cfa_issued_at);

        // Repudiation Period Status
        $repudiationPeriodDays = (int) config('katarungang_pambarangay.repudiation_period_days', 10);
        $repudiationEndsAt = $case->repudiation_ends_at
            ?? ($case->settled_on ? Carbon::parse($case->settled_on)->addDays($repudiationPeriodDays) : null);
        $isRepudiated = ! empty($case->repudiated_at);
        $isRepudiationActive = $settlementSigned && ! $isRepudiated && $repudiationEndsAt && now()->lt($repudiationEndsAt);
        $isRepudiationPassed = $settlementSigned && ! $isRepudiated && $repudiationEndsAt && now()->gte($repudiationEndsAt);

        $isMediationFailed = $case->mediation_outcome === 'failed'
            || $latestHearing?->status === 'unsettled'
            || $isRepudiated;

        // Working-day mediation clock (anchored on first scheduled hearing date)
        $mediationClock = $this->calculateMediationClock($case, $activeHearings->first());

        $hearingLabel = $latestHearing ? "Hearing #{$currentAttemptNo}" : null;

        // Milestones (strictly ONE "next" card at a time; later cards are "locked")
        $outcomeDone = $isSettledOutcome || $isEndorsedOrCfa || $isMediationFailed;
        $mediationDone = $latestHearingOutcomeRecorded && ! $isAbsentOrNotServed;
        $settlementDone = ($settlementSigned && $isRepudiationPassed) || ! empty($case->cfa_issued_at);

        $milestones = $this->buildMilestones(
            complaintSigned: $complaintSigned,
            complaintSignedAt: $case->complaint_signed_received_at?->format('M d, Y'),
            complaintPrintedDate: $complaintPrintedDate,
            hearingAndSummonsIssued: $hearingAndSummonsIssued,
            hearingIssuedAt: $activeSummons?->issued_on?->format('M d, Y') ?? $latestHearing?->created_at?->format('M d, Y'),
            serviceSatisfied: $serviceSatisfied,
            serviceAt: $activeSummons?->served_on?->format('M d, Y') ?? ($serviceSkipped ? 'Skipped' : null),
            mediationDone: $mediationDone,
            mediationAt: $mediationDone ? $latestHearing?->scheduled_at?->format('M d, Y') : null,
            outcomeDone: $outcomeDone,
            outcomeAt: $outcomeDone ? ($case->mediation_outcome ? ucfirst(str_replace('_', ' ', $case->mediation_outcome)) : ($isEndorsedOrCfa ? 'Endorsed' : null)) : null,
            settlementDone: $settlementDone,
            settlementAt: $case->cfa_issued_at?->format('M d, Y') ?? $case->settled_on?->format('M d, Y'),
            hearingLabel: $hearingLabel,
            isAbsentOrNotServed: $isAbsentOrNotServed,
        );

        // State 1: Signed complaint pending
        if (! $complaintSigned) {
            return [
                'code' => 'STEP_1_SIGN_COMPLAINT',
                'step_number' => 1,
                'title' => 'Print the complaint for in-person signing',
                'description' => 'The complainant must sign the formal complaint (KP Form No. 1) in person before the Punong Barangay or Barangay Secretary before summons can be issued.',
                'primary_action' => [
                    'label' => 'Mark Signed Complaint Received',
                    'icon' => 'fa-solid fa-file-signature',
                    'modal' => 'modal-mark-signed-complaint',
                ],
                'more_actions' => [
                    [
                        'label' => 'Print Complaint Form',
                        'icon' => 'fa-solid fa-print',
                        'url' => route('admin.cases.printables.complaint', $case),
                        'target' => '_blank',
                    ],
                ],
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
                'reminder_line' => null,
                'next_attempt_no' => 1,
            ];
        }

        // State 6: Settled flow
        if ($isSettledOutcome && ! $isRepudiated) {
            if (! $settlementSigned) {
                return [
                    'code' => 'STEP_5_SETTLEMENT',
                    'step_number' => 5,
                    'title' => 'Prepare and sign Amicable Settlement (KP Form 16)',
                    'description' => 'Parties have reached an agreement. Print KP Form 16 for in-person signing, then record receipt of the signed copy.',
                    'primary_action' => [
                        'label' => 'Mark Signed Settlement Received',
                        'icon' => 'fa-solid fa-file-circle-check',
                        'modal' => 'modal-mark-signed-settlement',
                    ],
                    'more_actions' => [
                        [
                            'label' => 'Print Settlement (Form 16)',
                            'icon' => 'fa-solid fa-print',
                            'url' => route('admin.cases.printables.settlement', $case),
                            'target' => '_blank',
                        ],
                    ],
                    'milestones' => $milestones,
                    'mediation_clock' => $mediationClock,
                    'next_attempt_no' => $nextAttemptNo,
                ];
            }

            if ($isRepudiationActive) {
                $daysRemaining = (int) now()->startOfDay()->diffInDays($repudiationEndsAt->startOfDay(), false);

                return [
                    'code' => 'STEP_6_REPUDIATION',
                    'step_number' => 6,
                    'title' => "Settled — 10-day repudiation period active ({$daysRemaining} days remaining)",
                    'description' => "Under Sec. 418 of RA 7160, any party may repudiate the settlement until {$repudiationEndsAt->format('M d, Y')} on grounds of fraud, violence, or intimidation.",
                    'primary_action' => [
                        'label' => 'Record Repudiation',
                        'icon' => 'fa-solid fa-shield-halved',
                        'modal' => 'modal-record-repudiation',
                    ],
                    'more_actions' => [
                        [
                            'label' => 'Print Settlement (Form 16)',
                            'icon' => 'fa-solid fa-print',
                            'url' => route('admin.cases.printables.settlement', $case),
                            'target' => '_blank',
                        ],
                    ],
                    'milestones' => $milestones,
                    'repudiation_countdown' => [
                        'ends_at' => $repudiationEndsAt->format('M d, Y'),
                        'days_remaining' => $daysRemaining,
                    ],
                    'mediation_clock' => null,
                    'next_attempt_no' => $nextAttemptNo,
                ];
            }

            $transmittalReminder = empty($case->court_transmitted_at);

            return [
                'code' => 'STEP_6_SETTLED_FINAL',
                'step_number' => 6,
                'title' => 'Amicable settlement final & executory',
                'description' => $transmittalReminder
                    ? 'The 10-day repudiation period elapsed without challenge. Transmit a copy to the Municipal Trial Court within 5 days (Sec. 419, RA 7160).'
                    : 'Settlement is final and executory. Transmittal to Municipal Trial Court recorded.',
                'primary_action' => $transmittalReminder ? [
                    'label' => 'Mark Transmitted to Court',
                    'icon' => 'fa-solid fa-landmark',
                    'modal' => 'modal-mark-court-transmitted',
                ] : null,
                'more_actions' => [
                    [
                        'label' => 'Print Settlement (Form 16)',
                        'icon' => 'fa-solid fa-print',
                        'url' => route('admin.cases.printables.settlement', $case),
                        'target' => '_blank',
                    ],
                ],
                'milestones' => $milestones,
                'mediation_clock' => null,
                'next_attempt_no' => $nextAttemptNo,
            ];
        }

        // Already endorsed / CFA issued
        if ($isEndorsedOrCfa) {
            $cfaIssued = ! empty($case->cfa_issued_at);

            return [
                'code' => 'STEP_ENDORSED',
                'step_number' => 6,
                'title' => $cfaIssued ? 'Certificate to File Action (KP Form 20) Issued' : 'Endorsed to Pangkat ng Tagapagkasundo',
                'description' => $cfaIssued
                    ? 'Official Certificate to File Action has been issued. Complainant may now file this matter in court.'
                    : 'Case has been endorsed to the 3-member Pangkat for conciliation.',
                'primary_action' => ! $cfaIssued ? [
                    'label' => 'Open Escalation & Conciliation',
                    'icon' => 'fa-solid fa-users-gear',
                    'tab' => 'escalation',
                ] : null,
                'more_actions' => array_values(array_filter([
                    $cfaIssued ? [
                        'label' => 'Print CFA (Form 20)',
                        'icon' => 'fa-solid fa-print',
                        'url' => route('admin.cases.printables.cfa', $case),
                        'target' => '_blank',
                    ] : null,
                ])),
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
                'next_attempt_no' => $nextAttemptNo,
            ];
        }

        // State 7: Day 15 passed, not settled (or explicit mediation failed)
        if ($mediationClock['is_overdue'] || $isMediationFailed) {
            return [
                'code' => 'STEP_7_ENDORSE_PANGKAT',
                'step_number' => 5,
                'title' => 'Endorse to Pangkat',
                'description' => $isMediationFailed
                    ? 'Mediation did not reach a settlement. Endorse the case to the Pangkat ng Tagapagkasundo.'
                    : '15 working days reached without settlement.',
                'primary_action' => [
                    'label' => 'Endorse to Pangkat',
                    'icon' => 'fa-solid fa-users-gear',
                    'tab' => 'escalation',
                ],
                'more_actions' => [
                    [
                        'label' => 'Issue CFA (reason required)',
                        'icon' => 'fa-solid fa-certificate',
                        'modal' => 'modal-issue-cfa',
                    ],
                ],
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
                'next_attempt_no' => $nextAttemptNo,
            ];
        }

        // State 2: Signed, no hearing yet
        if (! $hearingAndSummonsIssued) {
            $summonsDueDate = $this->calculateSummonsDueDate($case->complaint_signed_received_at);
            $isOverdue = $summonsDueDate && CarbonImmutable::today()->greaterThan($summonsDueDate);

            return [
                'code' => 'STEP_2_SCHEDULE_AND_SUMMONS',
                'step_number' => 2,
                'title' => 'Schedule the mediation hearing and issue summons',
                'description' => 'Set the first hearing date once to generate Hearing #1 and Summons #1 together.',
                'reminder_line' => $summonsDueDate ? [
                    'text' => 'Summons are due within 1 working day of receiving the complaint (by '.$summonsDueDate->format('M d, Y').').',
                    'is_overdue' => $isOverdue,
                ] : null,
                'primary_action' => [
                    'label' => 'Schedule Hearing & Issue Summons',
                    'icon' => 'fa-solid fa-calendar-plus',
                    'modal' => 'modal-schedule-and-issue-summons',
                ],
                'more_actions' => [],
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
                'next_attempt_no' => 1,
            ];
        }

        // State 5: Absent or not served, day < 15
        if ($isAbsentOrNotServed) {
            return [
                'code' => 'STEP_5_NEXT_HEARING',
                'step_number' => 3,
                'title' => "Schedule the next hearing (Summons #{$nextAttemptNo})",
                'description' => $serviceNotServed
                    ? "Summons #{$currentAttemptNo} could not be served. Schedule the next hearing to generate Summons #{$nextAttemptNo}."
                    : "A party was absent or the hearing was postponed. Schedule Hearing #{$nextAttemptNo} and issue Summons #{$nextAttemptNo}.",
                'primary_action' => [
                    'label' => 'Schedule Next Hearing',
                    'icon' => 'fa-solid fa-calendar-plus',
                    'modal' => 'modal-schedule-and-issue-summons',
                    'reschedule_hearing_id' => $latestHearing->id,
                ],
                'more_actions' => [],
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
                'next_attempt_no' => $nextAttemptNo,
            ];
        }

        // State 3: Summons issued, not served
        if (! $serviceSatisfied) {
            return [
                'code' => 'STEP_3_SERVE_SUMMONS',
                'step_number' => 3,
                'title' => "Print and serve Summons #{$currentAttemptNo}",
                'description' => "Deliver KP Form No. 9 (Summons #{$currentAttemptNo}) to the respondent and record proof of service before the hearing.",
                'primary_action' => [
                    'label' => 'Record Service',
                    'icon' => 'fa-solid fa-clipboard-check',
                    'modal' => 'modal-record-summons-service',
                    'summons_id' => $activeSummons->id,
                ],
                'more_actions' => [
                    [
                        'label' => 'Print Summons (Form 9)',
                        'icon' => 'fa-solid fa-envelope-open-text',
                        'url' => route('admin.cases.printables.summons', [$case, $activeSummons]),
                        'target' => '_blank',
                    ],
                    [
                        'label' => 'Print Notice (Complainant)',
                        'icon' => 'fa-solid fa-calendar-check',
                        'url' => route('admin.cases.printables.conciliation-notice', [$case, $latestHearing]),
                        'target' => '_blank',
                    ],
                    [
                        'label' => 'Reschedule',
                        'icon' => 'fa-solid fa-arrows-rotate',
                        'modal' => 'modal-schedule-and-issue-summons',
                        'reschedule_hearing_id' => $latestHearing->id,
                    ],
                    [
                        'label' => 'Endorse to Pangkat early (reason required)',
                        'icon' => 'fa-solid fa-users-gear',
                        'tab' => 'escalation',
                    ],
                    [
                        'label' => 'Issue CFA (reason required)',
                        'icon' => 'fa-solid fa-certificate',
                        'modal' => 'modal-issue-cfa',
                    ],
                ],
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
                'next_attempt_no' => $nextAttemptNo,
            ];
        }

        // State 4a: Served, before hearing date
        $hearingDay = $latestHearing->scheduled_at->copy()->startOfDay();
        $today = Carbon::today();
        if ($today->lt($hearingDay)) {
            $daysUntil = (int) $today->diffInDays($hearingDay);
            $waitLabel = $daysUntil === 1 ? '1 day until hearing' : "{$daysUntil} days until hearing";

            return [
                'code' => 'STEP_4A_AWAITING_HEARING',
                'step_number' => 4,
                'title' => "Hearing on {$latestHearing->scheduled_at->format('M d, Y · g:i A')}",
                'description' => "Summons #{$currentAttemptNo} is served. {$waitLabel} at {$latestHearing->venue}.",
                'primary_action' => null,
                'status_pill' => $waitLabel,
                'more_actions' => [
                    [
                        'label' => 'Print Summons (Form 9)',
                        'icon' => 'fa-solid fa-print',
                        'url' => route('admin.cases.printables.summons', [$case, $activeSummons]),
                        'target' => '_blank',
                    ],
                    [
                        'label' => 'Print Notice (Complainant)',
                        'icon' => 'fa-solid fa-calendar-check',
                        'url' => route('admin.cases.printables.conciliation-notice', [$case, $latestHearing]),
                        'target' => '_blank',
                    ],
                    [
                        'label' => 'Reschedule',
                        'icon' => 'fa-solid fa-arrows-rotate',
                        'modal' => 'modal-schedule-and-issue-summons',
                        'reschedule_hearing_id' => $latestHearing->id,
                    ],
                    [
                        'label' => 'Record Hearing Outcome',
                        'icon' => 'fa-solid fa-gavel',
                        'modal' => 'modal-record-hearing-outcome',
                        'hearing_id' => $latestHearing->id,
                    ],
                ],
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
                'next_attempt_no' => $nextAttemptNo,
            ];
        }

        // State 4b: Hearing date reached, outcome pending
        return [
            'code' => 'STEP_4B_RECORD_OUTCOME',
            'step_number' => 4,
            'title' => "Record the outcome of Hearing #{$currentAttemptNo}",
            'description' => "Hearing #{$currentAttemptNo} ({$latestHearing->scheduled_at->format('M d, Y · g:i A')}) is ready for attendance and proceedings logging.",
            'primary_action' => [
                'label' => 'Record Hearing Outcome',
                'icon' => 'fa-solid fa-gavel',
                'modal' => 'modal-record-hearing-outcome',
                'hearing_id' => $latestHearing->id,
            ],
            'more_actions' => [
                [
                    'label' => 'Print Summons (Form 9)',
                    'icon' => 'fa-solid fa-print',
                    'url' => route('admin.cases.printables.summons', [$case, $activeSummons]),
                    'target' => '_blank',
                ],
                [
                    'label' => 'Reschedule',
                    'icon' => 'fa-solid fa-arrows-rotate',
                    'modal' => 'modal-schedule-and-issue-summons',
                    'reschedule_hearing_id' => $latestHearing->id,
                ],
                [
                    'label' => 'Endorse to Pangkat early (reason required)',
                    'icon' => 'fa-solid fa-users-gear',
                    'tab' => 'escalation',
                ],
                [
                    'label' => 'Issue CFA (reason required)',
                    'icon' => 'fa-solid fa-certificate',
                    'modal' => 'modal-issue-cfa',
                ],
            ],
            'milestones' => $milestones,
            'mediation_clock' => $mediationClock,
            'next_attempt_no' => $nextAttemptNo,
        ];
    }

    /**
     * Build the 6 milestone cards with strictly ONE 'next' card at a time.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildMilestones(
        bool $complaintSigned,
        ?string $complaintSignedAt,
        ?string $complaintPrintedDate,
        bool $hearingAndSummonsIssued,
        ?string $hearingIssuedAt,
        bool $serviceSatisfied,
        ?string $serviceAt,
        bool $mediationDone,
        ?string $mediationAt,
        bool $outcomeDone,
        ?string $outcomeAt,
        bool $settlementDone,
        ?string $settlementAt,
        ?string $hearingLabel,
        bool $isAbsentOrNotServed,
    ): array {
        $nextAssigned = false;
        $assignState = function (bool $isDone) use (&$nextAssigned): string {
            if ($isDone) {
                return 'done';
            }
            if (! $nextAssigned) {
                $nextAssigned = true;

                return 'next';
            }

            return 'locked';
        };

        $signedState = $assignState($complaintSigned);
        $hearingState = $assignState($hearingAndSummonsIssued && ! $isAbsentOrNotServed);
        $servedState = $assignState($serviceSatisfied && ! $isAbsentOrNotServed);
        $mediationState = $assignState($mediationDone);
        $outcomeState = $assignState($outcomeDone);
        $settlementState = $assignState($settlementDone);

        return [
            [
                'id' => 'milestone_signed',
                'title' => 'Signed',
                'full_title' => 'Signed Complaint Received',
                'completed' => $signedState === 'done',
                'completed_at' => $complaintSignedAt,
                'subtext' => $complaintPrintedDate ? 'Printed '.$complaintPrintedDate : null,
                'hearing_label' => null,
                'state' => $signedState,
            ],
            [
                'id' => 'milestone_hearing_summons',
                'title' => 'Hearing & Summons',
                'full_title' => 'Hearing Scheduled & Summons Issued',
                'completed' => $hearingState === 'done',
                'completed_at' => $hearingIssuedAt,
                'subtext' => null,
                'hearing_label' => $hearingLabel,
                'state' => $hearingState,
            ],
            [
                'id' => 'milestone_served',
                'title' => 'Served',
                'full_title' => 'Summons Service Recorded',
                'completed' => $servedState === 'done',
                'completed_at' => $serviceAt,
                'subtext' => null,
                'hearing_label' => $hearingLabel,
                'state' => $servedState,
            ],
            [
                'id' => 'milestone_mediation',
                'title' => 'Mediation',
                'full_title' => '15-Working-Day Mediation Proceeding',
                'completed' => $mediationState === 'done',
                'completed_at' => $mediationAt,
                'subtext' => null,
                'hearing_label' => $hearingLabel,
                'state' => $mediationState,
            ],
            [
                'id' => 'milestone_outcome',
                'title' => 'Outcome',
                'full_title' => 'Mediation Outcome / Endorsement',
                'completed' => $outcomeState === 'done',
                'completed_at' => $outcomeAt,
                'subtext' => null,
                'hearing_label' => null,
                'state' => $outcomeState,
            ],
            [
                'id' => 'milestone_settlement',
                'title' => 'Settlement',
                'full_title' => 'Amicable Settlement / Certification',
                'completed' => $settlementState === 'done',
                'completed_at' => $settlementAt,
                'subtext' => null,
                'hearing_label' => null,
                'state' => $settlementState,
            ],
        ];
    }

    /**
     * Working-day mediation clock anchored on the FIRST scheduled hearing date.
     * Moves only if the first hearing is rescheduled BEFORE its date arrives.
     *
     * @return array<string, mixed>
     */
    public function calculateMediationClock(BarangayCase $case, ?Hearing $firstActiveHearing = null): array
    {
        $period = (int) config('katarungang_pambarangay.mediation_period_days', 15);
        $dayOneIsHearingDate = (bool) config('katarungang_pambarangay.day_one_is_hearing_date', true);
        $holidays = $this->getHolidays();

        $anchorCarbon = $this->resolveAnchorDate($case, $firstActiveHearing);

        if (! $anchorCarbon) {
            return [
                'is_started' => false,
                'anchor_date' => null,
                'end_date' => null,
                'day_current' => 0,
                'period' => $period,
                'display' => 'Starts at first scheduled hearing (TBD)',
                'days_remaining' => $period,
                'is_overdue' => false,
                'is_warning' => false,
            ];
        }

        $anchorDate = CarbonImmutable::parse($anchorCarbon)->startOfDay();
        $endDate = $this->computePeriodEndDate($anchorDate, $period, $dayOneIsHearingDate, $holidays);
        $today = CarbonImmutable::today();

        if ($today->lt($anchorDate)) {
            return [
                'is_started' => false,
                'anchor_date' => $anchorDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'day_current' => 0,
                'period' => $period,
                'display' => "Starts {$anchorDate->format('M d, Y')} (first hearing)",
                'days_remaining' => $period,
                'is_overdue' => false,
                'is_warning' => false,
            ];
        }

        $isOverdue = $today->gt($endDate);
        $dayCurrent = $this->countWorkingDaysElapsed($anchorDate, $today, $dayOneIsHearingDate, $holidays);
        $dayDisplay = min($period, max(1, $dayCurrent));
        $workingDaysRemaining = $isOverdue
            ? 0
            : max(0, $period - $dayDisplay);
        $isWarning = ! $isOverdue && $workingDaysRemaining <= 2;

        if ($isOverdue) {
            $display = '15 working days reached without settlement';
        } else {
            $display = "Working day {$dayDisplay} of {$period} - ends {$endDate->format('M d, Y')}";
        }

        return [
            'is_started' => true,
            'anchor_date' => $anchorDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'day_current' => $dayDisplay,
            'period' => $period,
            'display' => $display,
            'days_remaining' => $workingDaysRemaining,
            'is_overdue' => $isOverdue,
            'is_warning' => $isWarning,
        ];
    }

    /**
     * Compute working day X of 15 for any specific hearing date.
     *
     * @return array{day: int, period: int, beyond_end: bool, end_date: ?string}
     */
    public function workingDayForDate(BarangayCase $case, CarbonInterface $targetDate, ?Hearing $firstActiveHearing = null): array
    {
        $period = (int) config('katarungang_pambarangay.mediation_period_days', 15);
        $dayOneIsHearingDate = (bool) config('katarungang_pambarangay.day_one_is_hearing_date', true);
        $holidays = $this->getHolidays();

        $anchorCarbon = $this->resolveAnchorDate($case, $firstActiveHearing) ?? $targetDate;
        $anchorDate = CarbonImmutable::parse($anchorCarbon)->startOfDay();
        $target = CarbonImmutable::parse($targetDate)->startOfDay();
        $endDate = $this->computePeriodEndDate($anchorDate, $period, $dayOneIsHearingDate, $holidays);

        $day = $this->countWorkingDaysElapsed($anchorDate, $target, $dayOneIsHearingDate, $holidays);

        return [
            'day' => max(1, $day),
            'period' => $period,
            'beyond_end' => $target->gt($endDate),
            'end_date' => $endDate->toDateString(),
        ];
    }

    /**
     * Resolve the fixed/movable anchor date:
     * Once the earliest hearing date has passed, it stays fixed even if later rescheduled.
     */
    protected function resolveAnchorDate(BarangayCase $case, ?Hearing $firstActiveHearing = null): ?CarbonInterface
    {
        $today = CarbonImmutable::today();

        $earliestHearing = $case->hearings()->orderBy('sequence_no')->orderBy('id')->first();
        if ($earliestHearing && CarbonImmutable::parse($earliestHearing->scheduled_at)->startOfDay()->lte($today)) {
            return $earliestHearing->scheduled_at;
        }

        if ($firstActiveHearing) {
            return $firstActiveHearing->scheduled_at;
        }

        if (! empty($case->first_meeting_on)) {
            return CarbonImmutable::parse($case->first_meeting_on);
        }

        return null;
    }

    protected function computePeriodEndDate(CarbonImmutable $anchorDate, int $period, bool $dayOneIsHearingDate, array $holidays): CarbonImmutable
    {
        $daysToAdd = $dayOneIsHearingDate ? max(0, $period - 1) : $period;
        if ($daysToAdd === 0) {
            return $anchorDate;
        }

        return WorkingDays::addWorkingDays($anchorDate, $daysToAdd, $holidays);
    }

    protected function countWorkingDaysElapsed(CarbonImmutable $anchorDate, CarbonImmutable $targetDate, bool $dayOneIsHearingDate, array $holidays): int
    {
        $start = $dayOneIsHearingDate ? $anchorDate : $anchorDate->addDay();
        $count = WorkingDays::countWorkingDays($start, $targetDate, $holidays);

        if ($dayOneIsHearingDate && $targetDate->gte($anchorDate) && $count === 0) {
            return 1;
        }

        return $count;
    }

    /**
     * Merge config holidays and admin-configured calendar holidays.
     *
     * @return array<int, string>
     */
    public function getHolidays(): array
    {
        $configHolidays = (array) config('katarungang_pambarangay.holidays', []);
        $settingHolidays = (array) setting('calendar.holidays', []);
        $dates = [];

        foreach (array_merge($configHolidays, $settingHolidays) as $item) {
            if (is_array($item)) {
                $dates[] = (string) ($item['date'] ?? '');
            } else {
                $dates[] = (string) $item;
            }
        }

        return array_values(array_unique(array_filter($dates)));
    }

    /**
     * Calculate 1 working day after signed complaint received.
     */
    public function calculateSummonsDueDate(?Carbon $complaintSignedAt): ?CarbonImmutable
    {
        if (! $complaintSignedAt) {
            return null;
        }

        return WorkingDays::addWorkingDays(CarbonImmutable::parse($complaintSignedAt), 1, $this->getHolidays());
    }
}
