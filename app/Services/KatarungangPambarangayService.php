<?php

namespace App\Services;

use App\Models\BarangayCase;
use App\Models\Hearing;
use App\Models\Summons;
use App\Support\CaseStatus;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class KatarungangPambarangayService
{
    /**
     * Compute current milestone and next action for a case.
     */
    public function determineNextStep(BarangayCase $case): array
    {
        // 1. Signed Complaint
        $complaintSigned = ! empty($case->complaint_signed_received_at);
        $complaintPrintedDate = $case->complaint_printed_at?->format('M d, Y');

        // 2. Hearing & Summons (Issued together)
        $latestHearing = $case->hearings()->orderBy('scheduled_at', 'desc')->first();
        $activeSummons = $case->activeSummons()->latest()->first();
        $hearingAndSummonsIssued = $latestHearing && $activeSummons;

        // 3. Summons Served
        $summonsServed = ! empty($case->summons_served_at) || ($activeSummons && ! empty($activeSummons->served_on));

        // 4. Mediation (Clock running once first meeting held)
        $mediationStarted = ! empty($case->mediation_started_at);

        // 5. Outcome Recorded
        $hearingCompleted = ! empty($case->mediation_outcome) || ($latestHearing && ! empty($latestHearing->status) && $latestHearing->status !== 'scheduled');

        // 6. Settlement
        $isSettledOutcome = $case->mediation_outcome === 'settled' || ($latestHearing && $latestHearing->status === 'settled') || $case->status === CaseStatus::SETTLED;
        $settlementSigned = $isSettledOutcome && ! empty($case->settled_on);

        // Repudiation Period Status
        $repudiationPeriodDays = config('katarungang_pambarangay.repudiation_period_days', 10);
        $repudiationEndsAt = $case->repudiation_ends_at ?? ($case->settled_on ? Carbon::parse($case->settled_on)->addDays($repudiationPeriodDays) : null);
        $isRepudiated = ! empty($case->repudiated_at);
        $isRepudiationActive = $settlementSigned && ! $isRepudiated && $repudiationEndsAt && now()->lt($repudiationEndsAt);
        $isRepudiationPassed = $settlementSigned && ! $isRepudiated && $repudiationEndsAt && now()->gte($repudiationEndsAt);

        // Failed / Repudiated
        $isMediationFailed = $case->mediation_outcome === 'failed' || ($latestHearing && $latestHearing->status === 'unsettled') || $isRepudiated;

        // 6 Clean Milestones
        $milestones = [
            [
                'id' => 'milestone_signed',
                'title' => 'Signed',
                'full_title' => 'Signed Complaint Received',
                'completed' => $complaintSigned,
                'completed_at' => $case->complaint_signed_received_at?->format('M d, Y'),
                'subtext' => $complaintPrintedDate ? 'Printed '.$complaintPrintedDate : null,
                'state' => $complaintSigned ? 'done' : 'next',
            ],
            [
                'id' => 'milestone_hearing_summons',
                'title' => 'Hearing & Summons',
                'full_title' => 'Hearing Scheduled & Summons Issued',
                'completed' => $hearingAndSummonsIssued,
                'completed_at' => $activeSummons?->issued_on?->format('M d, Y') ?? $latestHearing?->created_at?->format('M d, Y'),
                'subtext' => null,
                'state' => $hearingAndSummonsIssued ? 'done' : ($complaintSigned ? 'next' : 'locked'),
            ],
            [
                'id' => 'milestone_served',
                'title' => 'Served',
                'full_title' => 'Summons Service Recorded',
                'completed' => $summonsServed,
                'completed_at' => $case->summons_served_at?->format('M d, Y') ?? $activeSummons?->served_on?->format('M d, Y'),
                'subtext' => null,
                'state' => $summonsServed ? 'done' : ($hearingAndSummonsIssued ? 'next' : 'locked'),
            ],
            [
                'id' => 'milestone_mediation',
                'title' => 'Mediation',
                'full_title' => '15-Day Mediation Proceeding',
                'completed' => $mediationStarted || $hearingCompleted,
                'completed_at' => $case->mediation_started_at?->format('M d, Y'),
                'subtext' => null,
                'state' => ($mediationStarted && ! $hearingCompleted) ? 'in_progress' : (($mediationStarted || $hearingCompleted) ? 'done' : ($hearingAndSummonsIssued ? 'next' : 'locked')),
            ],
            [
                'id' => 'milestone_outcome',
                'title' => 'Outcome',
                'full_title' => 'Mediation Hearing Outcome Recorded',
                'completed' => $hearingCompleted,
                'completed_at' => $case->mediation_outcome ? ucfirst($case->mediation_outcome) : null,
                'subtext' => null,
                'state' => $hearingCompleted ? 'done' : ($hearingAndSummonsIssued ? 'next' : 'locked'),
            ],
            [
                'id' => 'milestone_settlement',
                'title' => 'Settlement',
                'full_title' => 'Amicable Settlement / Certification',
                'completed' => ($settlementSigned && $isRepudiationPassed) || ! empty($case->cfa_issued_at),
                'completed_at' => $case->cfa_issued_at?->format('M d, Y') ?? $case->settled_on?->format('M d, Y'),
                'subtext' => null,
                'state' => (($settlementSigned && $isRepudiationPassed) || ! empty($case->cfa_issued_at))
                    ? 'done'
                    : (($isSettledOutcome || $isMediationFailed) ? 'next' : 'locked'),
            ],
        ];

        // 15-Day Mediation Clock Calculation (Sec. 410b RA 7160)
        // Starts ONLY when first meeting is actually held with parties present
        $mediationClock = $this->calculateMediationClock($case, $latestHearing);

        // Summons Sequence & Ordinals
        $summonsCount = $case->summons()->whereNull('voided_at')->count();
        $currentSeq = $activeSummons?->sequence_no ?? max(1, $summonsCount);
        $nextSeq = $summonsCount + 1;
        $summonsOrdinal = match ($currentSeq) {
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            default => "{$currentSeq}th",
        };
        $nextOrdinal = match ($nextSeq) {
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            default => "{$nextSeq}th",
        };

        // Step 1: Signed Complaint Pending
        if (! $complaintSigned) {
            return [
                'code' => 'STEP_1_SIGN_COMPLAINT',
                'step_number' => 1,
                'title' => 'Print the complaint for in-person signing',
                'description' => 'The complainant must sign the formal complaint (KP Form No. 1) in person before the Punong Barangay or Barangay Secretary before summons can be issued.',
                'primary_action' => [
                    'label' => 'Print Complaint Form',
                    'icon' => 'fa-solid fa-print',
                    'url' => route('admin.cases.printables.complaint', $case),
                    'target' => '_blank',
                ],
                'secondary_action' => [
                    'label' => 'Mark Signed Complaint Received',
                    'icon' => 'fa-solid fa-file-signature',
                    'modal' => 'modal-mark-signed-complaint',
                ],
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
                'reminder_line' => null,
            ];
        }

        // Step 2: Merge Schedule Hearing & Issue Summons
        if (! $hearingAndSummonsIssued) {
            $summonsDueDate = $this->calculateSummonsDueDate($case->complaint_signed_received_at);
            $isOverdue = $summonsDueDate && CarbonImmutable::today()->greaterThan($summonsDueDate);

            return [
                'code' => 'STEP_2_SCHEDULE_AND_SUMMONS',
                'step_number' => 2,
                'title' => 'Schedule the mediation hearing and issue summons',
                'description' => 'The Punong Barangay summons the respondent, with notice to the complainant, to appear for mediation. The summons is generated from the hearing date you set.',
                'reminder_line' => $summonsDueDate ? [
                    'text' => 'Summons are due within 1 working day of receiving the complaint (by '.$summonsDueDate->format('M d, Y').').',
                    'is_overdue' => $isOverdue,
                ] : null,
                'primary_action' => [
                    'label' => 'Schedule Hearing & Issue Summons',
                    'icon' => 'fa-solid fa-calendar-plus',
                    'modal' => 'modal-schedule-and-issue-summons',
                ],
                'secondary_action' => null,
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
            ];
        }

        // Step 3: Print, Serve Summons & Record Hearing Outcome
        if (! $summonsServed && ! $hearingCompleted) {
            return [
                'code' => 'STEP_3_SERVE_SUMMONS',
                'step_number' => 3,
                'title' => "Print and serve Summons #{$currentSeq} ({$summonsOrdinal} Notice)",
                'description' => "Deliver KP Form No. 9 ({$summonsOrdinal} Summons) to the respondent and notice to complainant. Once served or during the scheduled hearing session, record service or log the hearing outcome directly. If the respondent is absent or reset is needed, issue the {$nextOrdinal} Summons.",
                'primary_action' => [
                    'label' => 'Record Hearing Outcome',
                    'icon' => 'fa-solid fa-gavel',
                    'modal' => 'modal-record-hearing-outcome',
                ],
                'secondary_action' => [
                    'label' => 'Record Service',
                    'icon' => 'fa-solid fa-clipboard-check',
                    'modal' => 'modal-record-summons-service',
                ],
                'extra_actions' => [
                    [
                        'label' => 'Print Summons (Form 9) - Respondent',
                        'icon' => 'fa-solid fa-envelope-open-text',
                        'url' => route('admin.cases.printables.summons', [$case, $activeSummons]),
                        'target' => '_blank',
                    ],
                    [
                        'label' => 'Print Hearing Notice - Complainant',
                        'icon' => 'fa-solid fa-calendar-check',
                        'url' => route('admin.cases.printables.conciliation-notice', [$case, $latestHearing]),
                        'target' => '_blank',
                    ],
                    [
                        'label' => "Issue {$nextOrdinal} Summons / Reschedule",
                        'icon' => 'fa-solid fa-file-circle-plus',
                        'modal' => 'modal-reschedule-hearing',
                    ],
                ],
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
            ];
        }

        // Subsequent Summons Step (e.g. Respondent Absent / No-Show at previous hearing)
        if (in_array($case->mediation_outcome, ['respondent_absent', 'rescheduled'])) {
            $isOverLimit = $summonsCount >= 3;

            return [
                'code' => 'STEP_SUBSEQUENT_SUMMONS',
                'step_number' => 4,
                'title' => $case->mediation_outcome === 'respondent_absent'
                    ? "Respondent Absent — Issue {$nextOrdinal} Summons (Notice #{$nextSeq})"
                    : "Hearing Rescheduled — Issue {$nextOrdinal} Summons (Notice #{$nextSeq})",
                'description' => $case->mediation_outcome === 'respondent_absent'
                    ? "The respondent did not appear at the {$summonsOrdinal} hearing. Under Katarungang Pambarangay procedure, issue the {$nextOrdinal} Summons (KP Form 9). Note: In standard practice, up to 3 summonses are issued before referral to Pangkat or issuing a Certificate to File Action."
                    : "The previous hearing was reset. Issue the {$nextOrdinal} Summons (KP Form 9) with the new appearance schedule.",
                'primary_action' => [
                    'label' => "Issue {$nextOrdinal} Summons & Set Date",
                    'icon' => 'fa-solid fa-file-circle-plus',
                    'modal' => 'modal-reschedule-hearing',
                ],
                'secondary_action' => $isOverLimit ? [
                    'label' => 'Issue Certificate to File Action (Form 20)',
                    'icon' => 'fa-solid fa-certificate',
                    'modal' => 'modal-issue-cfa',
                ] : [
                    'label' => 'Record Hearing Outcome',
                    'icon' => 'fa-solid fa-gavel',
                    'modal' => 'modal-record-hearing-outcome',
                ],
                'extra_actions' => $isOverLimit ? [
                    [
                        'label' => 'Refer to Pangkat',
                        'icon' => 'fa-solid fa-users-gear',
                        'modal' => 'modal-refer-pangkat',
                    ],
                ] : [],
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
            ];
        }

        // Step 4: Hearing Outcome (Summons served, record outcome or await session)
        if (! $hearingCompleted) {
            $hearingDate = $latestHearing->scheduled_at;

            return [
                'code' => 'STEP_4_HEARING_OUTCOME',
                'step_number' => 4,
                'title' => "Mediation session ({$summonsOrdinal} Summons): {$hearingDate->format('M d, Y · g:i A')}",
                'description' => "Parties are summoned under {$summonsOrdinal} Summons to appear before the Punong Barangay. Record attendance and whether mediation succeeded, failed, or requires issuing a subsequent summons.",
                'primary_action' => [
                    'label' => 'Record Hearing Outcome',
                    'icon' => 'fa-solid fa-gavel',
                    'modal' => 'modal-record-hearing-outcome',
                ],
                'secondary_action' => [
                    'label' => "Issue {$nextOrdinal} Summons / Reschedule",
                    'icon' => 'fa-solid fa-file-circle-plus',
                    'modal' => 'modal-reschedule-hearing',
                ],
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
            ];
        }

        // Step 5: Settled Outcome Flow
        if ($isSettledOutcome && ! $isRepudiated) {
            if (! $settlementSigned) {
                return [
                    'code' => 'STEP_5_SETTLEMENT',
                    'step_number' => 5,
                    'title' => 'Prepare and sign Amicable Settlement (KP Form 16)',
                    'description' => 'Parties have reached an agreement. Draft the agreed terms in official KP Form 16, print for in-person signing by both parties and the Punong Barangay.',
                    'primary_action' => [
                        'label' => 'Print Settlement (Form 16)',
                        'icon' => 'fa-solid fa-print',
                        'url' => route('admin.cases.printables.settlement', $case),
                        'target' => '_blank',
                    ],
                    'secondary_action' => [
                        'label' => 'Mark Signed Settlement Received',
                        'icon' => 'fa-solid fa-file-circle-check',
                        'modal' => 'modal-mark-signed-settlement',
                    ],
                    'milestones' => $milestones,
                    'mediation_clock' => $mediationClock,
                ];
            }

            if ($isRepudiationActive) {
                $daysRemaining = now()->startOfDay()->diffInDays($repudiationEndsAt->startOfDay(), false);

                return [
                    'code' => 'STEP_6_REPUDIATION',
                    'step_number' => 6,
                    'title' => "Settled — 10-day repudiation period active ({$daysRemaining} days remaining)",
                    'description' => "Under Sec. 418 of RA 7160, any party may repudiate the settlement within 10 days on grounds of fraud, violence, or intimidation. Repudiation period ends {$repudiationEndsAt->format('M d, Y')}.",
                    'primary_action' => [
                        'label' => 'Record Repudiation',
                        'icon' => 'fa-solid fa-shield-halved',
                        'modal' => 'modal-record-repudiation',
                    ],
                    'secondary_action' => null,
                    'milestones' => $milestones,
                    'repudiation_countdown' => [
                        'ends_at' => $repudiationEndsAt->format('M d, Y'),
                        'days_remaining' => (int) $daysRemaining,
                    ],
                    'mediation_clock' => null,
                ];
            }

            // Repudiation Passed without challenge
            $transmittalReminder = empty($case->court_transmitted_at);

            return [
                'code' => 'STEP_6_SETTLED_FINAL',
                'step_number' => 6,
                'title' => 'Amicable settlement final & executory',
                'description' => $transmittalReminder
                    ? 'The 10-day repudiation period has elapsed without challenge. Statutory reminder: Transmit a copy of the settlement to the Municipal Trial Court within 5 days (Sec. 419, RA 7160).'
                    : 'Settlement is final and executory. Transmittal to Municipal Trial Court recorded.',
                'primary_action' => $transmittalReminder ? [
                    'label' => 'Mark Transmitted to Court',
                    'icon' => 'fa-solid fa-landmark',
                    'modal' => 'modal-mark-court-transmitted',
                ] : null,
                'secondary_action' => null,
                'milestones' => $milestones,
                'mediation_clock' => null,
            ];
        }

        // Mediation Failed or Repudiated
        if ($isMediationFailed) {
            $cfaIssued = ! empty($case->cfa_issued_at);

            return [
                'code' => 'STEP_H_MEDIATION_UNSUCCESSFUL',
                'step_number' => 5,
                'title' => $cfaIssued ? 'Certification to File Action (KP Form 20) Issued' : 'Mediation unsuccessful — next escalation step',
                'description' => $cfaIssued
                    ? 'Official Certificate to File Action has been granted. Complainant may now file this matter with the regular court or police.'
                    : 'Parties failed to reach an amicable settlement. You may endorse to the Pangkat ng Tagapagkasundo (Sec. 410c) or issue a Certificate to File Action (KP Form 20).',
                'primary_action' => [
                    'label' => 'Issue Certificate to File Action (Form 20)',
                    'icon' => 'fa-solid fa-certificate',
                    'modal' => 'modal-issue-cfa',
                ],
                'secondary_action' => [
                    'label' => 'Refer to Pangkat',
                    'icon' => 'fa-solid fa-users-gear',
                    'modal' => 'modal-refer-pangkat',
                ],
                'milestones' => $milestones,
                'mediation_clock' => $mediationClock,
            ];
        }

        return [
            'code' => 'STEP_COMPLETE',
            'step_number' => 6,
            'title' => 'Case Proceedings In Order',
            'description' => 'All milestones recorded according to Katarungang Pambarangay guidelines.',
            'primary_action' => null,
            'secondary_action' => null,
            'milestones' => $milestones,
            'mediation_clock' => null,
        ];
    }

    /**
     * Calculate statutory 15-day mediation clock.
     * Sec. 410(b) RA 7160: 15 days counted from first meeting of parties.
     */
    public function calculateMediationClock(BarangayCase $case, ?Hearing $latestHearing): array
    {
        $mediationPeriodDays = (int) config('katarungang_pambarangay.mediation_period_days', 15);
        $scheduledDateStr = $latestHearing?->scheduled_at?->format('M d, Y') ?? 'TBD';

        if (empty($case->mediation_started_at)) {
            return [
                'is_started' => false,
                'display' => "Starts at the first hearing ({$scheduledDateStr})",
                'days_remaining' => $mediationPeriodDays,
                'is_overdue' => false,
                'is_warning' => false,
            ];
        }

        $startDate = Carbon::parse($case->mediation_started_at);
        $deadline = $startDate->copy()->addDays($mediationPeriodDays);
        $today = Carbon::today();
        $daysRemaining = (int) $today->diffInDays($deadline->copy()->startOfDay(), false);
        $isOverdue = $daysRemaining < 0;
        $isWarning = $daysRemaining >= 0 && $daysRemaining <= 3;
        $dayCurrent = min($mediationPeriodDays, max(1, $mediationPeriodDays - $daysRemaining));

        if ($isOverdue) {
            $display = "Mediation period ended {$deadline->format('M d, Y')}. Refer to Pangkat.";
        } else {
            $display = "Day {$dayCurrent} of {$mediationPeriodDays}, ends {$deadline->format('M d, Y')}";
        }

        return [
            'is_started' => true,
            'start_date' => $startDate->format('M d, Y'),
            'deadline' => $deadline->format('M d, Y'),
            'days_remaining' => $daysRemaining,
            'display' => $display,
            'is_overdue' => $isOverdue,
            'is_warning' => $isWarning,
        ];
    }

    /**
     * Calculate 1 working day summons due date from complaint receipt.
     */
    protected function calculateSummonsDueDate(?Carbon $receivedAt): ?CarbonImmutable
    {
        if (! $receivedAt) {
            return null;
        }

        $holidays = (array) setting('calendar.holidays', []);
        $formattedHolidays = [];
        foreach ($holidays as $h) {
            $formattedHolidays[] = is_array($h) ? ($h['date'] ?? '') : (string) $h;
        }

        return WorkingDays::addWorkingDays(
            CarbonImmutable::parse($receivedAt),
            1,
            array_filter($formattedHolidays)
        );
    }
}
