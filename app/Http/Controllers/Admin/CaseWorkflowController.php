<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\CaseNote;
use App\Models\CaseParty;
use App\Models\Hearing;
use App\Models\Party;
use App\Models\Summons;
use App\Services\AuditLogger;
use App\Services\CaseStatusService;
use App\Support\CaseStatus;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaseWorkflowController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected CaseStatusService $statusService
    ) {}

    /**
     * Milestone 1: Mark signed complaint received in person.
     */
    public function markSignedComplaint(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'signed_at' => ['required', 'date', 'before_or_equal:today'],
            'received_by' => ['required', 'string', 'in:Punong Barangay,Barangay Secretary,Lupon Member'],
            'scan_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $filePath = null;
        if ($request->hasFile('scan_file')) {
            $filePath = $request->file('scan_file')->store("case-files/{$case->id}", 'private');
        }

        $case->update([
            'complaint_signed_received_at' => $validated['signed_at'],
            'complaint_signed_received_by' => $validated['received_by'],
            'complaint_signed_document_path' => $filePath ?? $case->complaint_signed_document_path,
        ]);

        if (! empty($validated['notes'])) {
            CaseNote::create([
                'case_id' => $case->id,
                'user_id' => $request->user()->id,
                'body' => "[Complaint Signing] Received by {$validated['received_by']}: {$validated['notes']}",
            ]);
        }

        $this->audit->log($request->user(), 'case.complaint_signed_received', $case, [], [
            'signed_at' => $validated['signed_at'],
            'received_by' => $validated['received_by'],
        ]);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Signed complaint recorded. Next: schedule the mediation hearing and issue summons.');
    }

    /**
     * Merged Step 2: Schedule Hearing & Issue Summons (Form 9 + Complainant Notice in one transaction).
     */
    public function scheduleHearingAndIssueSummons(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'venue' => ['required', 'string', 'max:255'],
            'presided_by' => ['nullable', 'exists:personnel,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'notify_channels' => ['nullable', 'boolean'],
            'warning_acknowledged' => ['nullable', 'boolean'],
        ]);

        $scheduledCarbon = Carbon::parse($validated['scheduled_at']);
        if ($scheduledCarbon->isPast()) {
            return back()->withInput()
                ->withErrors(['scheduled_at' => 'The hearing schedule must be set to a future date and time.']);
        }

        $isWeekend = $scheduledCarbon->isWeekend();
        $hour = (int) $scheduledCarbon->format('H');
        $isOffHours = $hour < 8 || $hour >= 17;

        if (($isWeekend || $isOffHours) && empty($validated['warning_acknowledged'])) {
            return back()->withInput()
                ->with('office_hours_warning', true)
                ->with('warning_message', 'The chosen schedule falls outside regular weekday office hours (Mon-Fri 8:00 AM - 5:00 PM). Please check the confirmation box to proceed.')
                ->withErrors(['scheduled_at' => 'The chosen schedule falls outside regular weekday office hours (Mon-Fri 8:00 AM - 5:00 PM). Please check the confirmation box to proceed.']);
        }

        DB::transaction(function () use ($case, $validated, $scheduledCarbon, $request) {
            // 1. Create Hearing record
            $hearing = Hearing::create([
                'case_id' => $case->id,
                'scheduled_at' => $scheduledCarbon,
                'venue' => $validated['venue'],
                'status' => 'scheduled',
                'presided_by_personnel_id' => $validated['presided_by'] ?? null,
            ]);

            // 2. Generate official Summons record
            $respondent = $case->caseParties()->where('role', 'respondent')->first();
            if (! $respondent) {
                $party = Party::create([
                    'full_name' => 'Respondent (Unassigned)',
                    'residency_status' => 'unverified',
                ]);
                $respondent = CaseParty::create([
                    'case_id' => $case->id,
                    'party_id' => $party->id,
                    'role' => 'respondent',
                ]);
            }
            $seqNo = ($case->summons()->max('sequence_no') ?? 0) + 1;

            $summons = Summons::create([
                'case_id' => $case->id,
                'respondent_case_party_id' => $respondent->id,
                'sequence_no' => $seqNo,
                'summons_number' => "SUMMONS-{$case->case_number}-#{$seqNo}",
                'issued_on' => now()->toDateString(),
                'issued_by' => $request->user()->id,
                'hearing_at' => $scheduledCarbon,
                'venue' => $validated['venue'],
                'remarks' => $validated['notes'] ?? null,
            ]);

            // Link hearing to summons
            $hearing->update(['summons_id' => $summons->id]);

            // Update case
            $case->update([
                'first_meeting_on' => $scheduledCarbon->toDateString(),
                'status' => CaseStatus::IN_MEDIATION,
                'notices_issued_at' => now(),
            ]);

            // 3. Notification dispatch logging
            if (! empty($validated['notify_channels'])) {
                $complainant = $case->caseParties()->where('role', 'complainant')->with('party')->first();
                $complainantEmail = $complainant?->party?->email;

                CaseNote::create([
                    'case_id' => $case->id,
                    'user_id' => $request->user()->id,
                    'body' => $complainantEmail
                        ? "[Notification] Hearing notice sent via email to {$complainantEmail}."
                        : '[Notification] No email contact found for complainant. Tanod physical service required.',
                ]);
            }

            if (! empty($validated['notes'])) {
                CaseNote::create([
                    'case_id' => $case->id,
                    'user_id' => $request->user()->id,
                    'body' => "[Hearing & Summons Issued] Hearing #{$seqNo} scheduled for {$scheduledCarbon->format('M d, Y g:i A')} at {$validated['venue']}. {$validated['notes']}",
                ]);
            }

            // 4. Audit Log
            $this->audit->log($request->user(), 'case.hearing_and_summons_issued', $case, [], [
                'hearing_id' => $hearing->id,
                'summons_id' => $summons->id,
                'scheduled_at' => $scheduledCarbon->toDateTimeString(),
                'venue' => $validated['venue'],
            ]);
        });

        return redirect()->route('admin.cases.show', $case)
            ->with('success', "Hearing scheduled for {$scheduledCarbon->format('M d, Y · g:i A')}. Summons generated. Print and serve them.");
    }

    /**
     * Reschedule Hearing & Regenerate Summons (marks previous summons as superseded).
     */
    public function rescheduleHearing(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'venue' => ['required', 'string', 'max:255'],
            'reason_preset' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $finalReason = ! empty($validated['reason'])
            ? $validated['reason']
            : (! empty($validated['reason_preset']) && $validated['reason_preset'] !== 'other' ? $validated['reason_preset'] : 'Hearing rescheduled upon official notice.');

        $scheduledCarbon = Carbon::parse($validated['scheduled_at']);
        if ($scheduledCarbon->isPast()) {
            return back()->withInput()->withErrors(['scheduled_at' => 'The new hearing schedule must be set to a future date and time.']);
        }

        DB::transaction(function () use ($case, $validated, $finalReason, $scheduledCarbon, $request) {
            // Mark existing active summons as superseded
            $case->activeSummons()->update([
                'superseded_at' => now(),
                'void_note' => 'Superseded by reschedule: '.$finalReason,
            ]);

            // Reset service milestone on case
            $case->update([
                'summons_service_status' => null,
                'summons_served_at' => null,
                'summons_served_by' => null,
                'first_meeting_on' => $scheduledCarbon->toDateString(),
            ]);

            // Create new Hearing session
            $hearing = Hearing::create([
                'case_id' => $case->id,
                'scheduled_at' => $scheduledCarbon,
                'venue' => $validated['venue'],
                'status' => 'scheduled',
                'outcome_note' => 'Rescheduled: '.$finalReason,
            ]);

            // Generate new Summons
            $respondent = $case->caseParties()->where('role', 'respondent')->first();
            if (! $respondent) {
                $party = Party::create([
                    'full_name' => 'Respondent (Unassigned)',
                    'residency_status' => 'unverified',
                ]);
                $respondent = CaseParty::create([
                    'case_id' => $case->id,
                    'party_id' => $party->id,
                    'role' => 'respondent',
                ]);
            }
            $seqNo = ($case->summons()->max('sequence_no') ?? 0) + 1;

            $summons = Summons::create([
                'case_id' => $case->id,
                'respondent_case_party_id' => $respondent->id,
                'sequence_no' => $seqNo,
                'summons_number' => "SUMMONS-{$case->case_number}-#{$seqNo}",
                'issued_on' => now()->toDateString(),
                'issued_by' => $request->user()->id,
                'hearing_at' => $scheduledCarbon,
                'venue' => $validated['venue'],
                'remarks' => 'Reschedule reason: '.$finalReason,
            ]);

            $hearing->update(['summons_id' => $summons->id]);

            CaseNote::create([
                'case_id' => $case->id,
                'user_id' => $request->user()->id,
                'body' => "[Hearing Rescheduled] New date: {$scheduledCarbon->format('M d, Y g:i A')} at {$validated['venue']}. Reason: {$finalReason}. Previous summons superseded. Re-service required.",
            ]);

            $this->audit->log($request->user(), 'case.hearing_rescheduled', $case, [], [
                'hearing_id' => $hearing->id,
                'summons_id' => $summons->id,
                'reason' => $finalReason,
                'scheduled_at' => $scheduledCarbon->toDateTimeString(),
            ]);
        });

        return redirect()->route('admin.cases.show', $case)
            ->with('success', "Hearing rescheduled for {$scheduledCarbon->format('M d, Y · g:i A')}. Previous summons marked Superseded. Please print and re-serve new summons.");
    }

    /**
     * Milestone 3: Record summons service on respondent.
     */
    public function recordSummonsService(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'served_at' => ['required', 'date', 'before_or_equal:today'],
            'service_method' => ['required', 'string', 'in:personal,substituted,refused,failed'],
            'served_by' => ['required', 'string', 'max:150'],
            'custom_served_by' => ['nullable', 'string', 'max:150'],
            'recipient_name' => ['nullable', 'string', 'max:150'],
            'recipient_relation' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $servedBy = ($validated['served_by'] === 'other' && ! empty($validated['custom_served_by']))
            ? $validated['custom_served_by']
            : $validated['served_by'];

        $isServed = in_array($validated['service_method'], ['personal', 'substituted']);

        $case->update([
            'summons_service_status' => $validated['service_method'],
            'summons_served_at' => $isServed ? $validated['served_at'] : null,
            'summons_served_by' => $servedBy,
            'summons_recipient_name' => $validated['recipient_name'] ?? null,
            'summons_recipient_relation' => $validated['recipient_relation'] ?? null,
        ]);

        // Update active summons model
        $activeSummons = $case->activeSummons()->latest()->first();
        if ($activeSummons && $isServed) {
            $activeSummons->update([
                'served_on' => $validated['served_at'],
                'service_result' => $validated['service_method'] === 'personal' ? 'received' : 'substituted',
                'remarks' => $validated['notes'] ?? null,
            ]);
        }

        if (! empty($validated['notes'])) {
            CaseNote::create([
                'case_id' => $case->id,
                'user_id' => $request->user()->id,
                'body' => "[Summons Service - {$validated['service_method']}] By {$servedBy}: {$validated['notes']}",
            ]);
        }

        $this->audit->log($request->user(), 'case.summons_service_recorded', $case, [], [
            'status' => $validated['service_method'],
            'served_at' => $validated['served_at'],
            'served_by' => $servedBy,
        ]);

        $msg = $isServed
            ? 'Summons service recorded successfully. Proceed with scheduled mediation.'
            : 'Summons service recorded as unserved/failed. You may reissue or reschedule summons.';

        return redirect()->route('admin.cases.show', $case)->with('success', $msg);
    }

    /**
     * Milestone 4 & 5: Record hearing outcome.
     * Note: 15-day mediation clock starts ONLY when first meeting is held with both parties present.
     */
    public function recordHearingOutcome(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'outcome' => ['required', 'string', 'in:settled,rescheduled,respondent_absent,complainant_absent,failed'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'hearing_held_on' => ['nullable', 'date', 'before_or_equal:today'],
            'both_parties_present' => ['nullable', 'boolean'],
            'reschedule_date' => ['nullable', 'date', 'after:now', 'required_if:outcome,rescheduled'],
            'reschedule_venue' => ['nullable', 'string', 'max:255'],
        ]);

        $hearing = $case->hearings()->latest()->first();
        $hearingHeldDate = $validated['hearing_held_on'] ?? now()->toDateString();
        $isBothPresent = in_array($validated['outcome'], ['settled', 'failed']) || ! empty($validated['both_parties_present']);

        // Sec. 410b: Clock starts only upon first meeting with parties present
        $mediationStartedAt = $case->mediation_started_at;
        if (! $mediationStartedAt && $isBothPresent) {
            $mediationStartedAt = Carbon::parse($hearingHeldDate);
        }

        if ($hearing) {
            $hearing->update([
                'status' => $validated['outcome'] === 'settled' ? 'settled' : ($validated['outcome'] === 'failed' ? 'unsettled' : 'rescheduled'),
                'complainant_attendance' => $validated['outcome'] === 'complainant_absent' ? 'failed_to_appear' : 'appeared',
                'respondent_attendance' => $validated['outcome'] === 'respondent_absent' ? 'failed_to_appear' : 'appeared',
                'outcome_note' => $validated['notes'] ?? $hearing->outcome_note,
            ]);
        }

        $caseUpdates = [
            'mediation_outcome' => $validated['outcome'],
            'mediation_started_at' => $mediationStartedAt,
        ];

        // If summons service was not separately logged prior to the hearing,
        // convening the parties / recording the outcome satisfies the service requirement.
        if (! $case->summons_served_at) {
            $caseUpdates['summons_served_at'] = $hearingHeldDate;
            $caseUpdates['summons_service_status'] = 'personal';
            $caseUpdates['summons_served_by'] = $request->user()->name;

            $activeSummons = $case->activeSummons()->latest()->first();
            if ($activeSummons && ! $activeSummons->served_on) {
                $activeSummons->update([
                    'served_on' => $hearingHeldDate,
                    'service_result' => 'received',
                    'remarks' => 'Service verified in person during mediation session.',
                ]);
            }
        }

        $case->update($caseUpdates);

        if ($validated['outcome'] === 'settled') {
            $case->update(['status' => CaseStatus::SETTLED]);
        } elseif ($validated['outcome'] === 'failed') {
            $case->update(['status' => CaseStatus::UNSETTLED]);
        } elseif ($validated['outcome'] === 'rescheduled') {
            Hearing::create([
                'case_id' => $case->id,
                'scheduled_at' => Carbon::parse($validated['reschedule_date']),
                'venue' => $validated['reschedule_venue'] ?: ($hearing?->venue ?? 'Barangay Hall'),
                'presided_by_personnel_id' => $hearing?->presided_by_personnel_id,
                'status' => 'scheduled',
            ]);
        }

        if (! empty($validated['notes'])) {
            CaseNote::create([
                'case_id' => $case->id,
                'user_id' => $request->user()->id,
                'body' => "[Hearing Outcome: {$validated['outcome']}] {$validated['notes']}",
            ]);
        }

        $this->audit->log($request->user(), 'case.hearing_outcome_recorded', $case, [], [
            'outcome' => $validated['outcome'],
            'mediation_started_at' => $mediationStartedAt?->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', "Mediation outcome recorded as '{$validated['outcome']}'.");
    }

    /**
     * Milestone 6: Mark signed settlement received.
     */
    public function markSignedSettlement(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'settled_on' => ['required', 'date', 'before_or_equal:today'],
            'terms' => ['required', 'string', 'min:10'],
            'scan_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $filePath = null;
        if ($request->hasFile('scan_file')) {
            $filePath = $request->file('scan_file')->store("case-files/{$case->id}", 'private');
        }

        $repudiationDays = config('katarungang_pambarangay.repudiation_period_days', 10);
        $repudiationEndsAt = Carbon::parse($validated['settled_on'])->addDays($repudiationDays);

        $case->update([
            'settled_on' => $validated['settled_on'],
            'settlement_terms' => $validated['terms'],
            'settlement_agreement_path' => $filePath ?? $case->settlement_agreement_path,
            'repudiation_ends_at' => $repudiationEndsAt,
            'status' => CaseStatus::SETTLED,
        ]);

        $this->audit->log($request->user(), 'case.settlement_signed_received', $case, [], [
            'settled_on' => $validated['settled_on'],
            'repudiation_ends_at' => $repudiationEndsAt->toDateString(),
        ]);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Signed Amicable Settlement received. 10-day repudiation period initiated.');
    }

    /**
     * Record settlement repudiation under Sec. 418.
     */
    public function recordRepudiation(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'repudiation_date' => ['required', 'date', 'before_or_equal:today'],
            'ground' => ['required', 'string', 'in:fraud,violence,intimidation'],
            'repudiated_by' => ['required', 'string', 'max:150'],
            'sworn_statement_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'explanation' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $filePath = null;
        if ($request->hasFile('sworn_statement_file')) {
            $filePath = $request->file('sworn_statement_file')->store("case-files/{$case->id}", 'private');
        }

        $case->update([
            'repudiated_at' => $validated['repudiation_date'],
            'repudiation_ground' => $validated['ground'],
            'repudiated_by' => $validated['repudiated_by'],
            'repudiation_document_path' => $filePath ?? $case->repudiation_document_path,
            'status' => CaseStatus::UNSETTLED,
        ]);

        CaseNote::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'body' => "[Repudiation Recorded] Filed by {$validated['repudiated_by']} on ground of ".strtoupper($validated['ground']).": {$validated['explanation']}",
        ]);

        $this->audit->log($request->user(), 'case.settlement_repudiated', $case, [], [
            'ground' => $validated['ground'],
            'repudiated_by' => $validated['repudiated_by'],
            'date' => $validated['repudiation_date'],
        ]);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Repudiation recorded. Settlement declared null and void. You may now refer to Pangkat or issue CFA.');
    }

    /**
     * Mark transmitted to Municipal Trial Court within 5 days.
     */
    public function markCourtTransmitted(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'transmitted_at' => ['required', 'date', 'before_or_equal:today'],
            'court_branch' => ['nullable', 'string', 'max:150'],
        ]);

        $case->update([
            'court_transmitted_at' => $validated['transmitted_at'],
        ]);

        $this->audit->log($request->user(), 'case.transmitted_to_court', $case, [], [
            'transmitted_at' => $validated['transmitted_at'],
            'court_branch' => $validated['court_branch'] ?? 'Municipal Trial Court in Cities (MTCC)',
        ]);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Transmittal to Municipal Trial Court recorded.');
    }

    /**
     * Issue Certificate to File Action (Form 20).
     */
    public function issueCfa(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'issued_at' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $case->update([
            'cfa_issued_at' => $validated['issued_at'],
            'status' => CaseStatus::ESCALATED,
        ]);

        $this->audit->log($request->user(), 'case.cfa_issued', $case, [], [
            'issued_at' => $validated['issued_at'],
            'reason' => $validated['reason'],
        ]);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Certification to File Action (KP Form 20) issued.');
    }
}
