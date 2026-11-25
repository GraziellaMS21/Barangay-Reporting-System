<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\CaseNote;
use App\Models\CaseParty;
use App\Models\CaseStatusLog;
use App\Models\Hearing;
use App\Models\Party;
use App\Models\Summons;
use App\Services\AuditLogger;
use App\Services\CaseStatusService;
use App\Services\KatarungangPambarangayService;
use App\Support\CaseStatus;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CaseWorkflowController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected CaseStatusService $statusService,
        protected KatarungangPambarangayService $kpService,
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
                'body' => "[Signed Complaint Received] By {$validated['received_by']}: {$validated['notes']}",
            ]);
        }

        $this->audit->log($request->user(), 'case.signed_complaint_received', $case, [], [
            'signed_at' => $validated['signed_at'],
            'received_by' => $validated['received_by'],
        ]);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Signed complaint recorded. Next: schedule the mediation hearing and issue summons.');
    }

    /**
     * Unified entry point: Schedule Hearing #N and Issue Summons #N together in one transaction.
     * Supports both first hearing and rescheduling/next hearing.
     */
    public function scheduleHearingAndIssueSummons(Request $request, BarangayCase $case): RedirectResponse
    {
        $rescheduleHearingId = $request->input('reschedule_hearing_id');
        $hasActiveHearing = $case->hearings()->whereNull('superseded_at')->exists();
        $isRescheduling = ! empty($rescheduleHearingId) || $hasActiveHearing;

        $rules = [
            'scheduled_at' => ['nullable', 'date'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['nullable', 'string'],
            'venue' => ['required', 'string', 'max:255'],
            'presided_by' => ['nullable', 'exists:personnel,id'],
            'respondent_case_party_id' => [
                'nullable',
                'integer',
                Rule::exists('case_parties', 'id')
                    ->where('case_id', $case->id)
                    ->where('role', 'respondent'),
            ],
            'reason_preset' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'notify_channels' => ['nullable', 'boolean'],
            'reschedule_hearing_id' => ['nullable', 'integer'],
        ];

        $validated = $request->validate($rules);

        if (! empty($validated['scheduled_at'])) {
            $scheduledCarbon = Carbon::parse($validated['scheduled_at']);
        } elseif (! empty($validated['scheduled_date'])) {
            $time = ! empty($validated['scheduled_time']) ? $validated['scheduled_time'] : '09:00';
            $scheduledCarbon = Carbon::parse($validated['scheduled_date'].' '.$time);
        } else {
            return back()->withInput()->withErrors(['scheduled_at' => 'Please select a hearing date and time.']);
        }

        if ($scheduledCarbon->isPast()) {
            return back()->withInput()
                ->withErrors(['scheduled_at' => 'The hearing schedule must be set to a future date and time.']);
        }

        $reason = null;
        if ($isRescheduling) {
            $reason = ! empty($validated['reason'])
                ? $validated['reason']
                : (! empty($validated['reason_preset']) && $validated['reason_preset'] !== 'other'
                    ? $validated['reason_preset']
                    : null);

            if (empty($reason)) {
                return back()->withInput()
                    ->withErrors(['reason' => 'A reason is required when rescheduling or scheduling the next hearing.']);
            }
        }

        $seqNo = 1;
        DB::transaction(function () use ($case, $validated, $scheduledCarbon, $request, $isRescheduling, $reason, &$seqNo) {
            // If rescheduling / scheduling next attempt, mark existing active pair as superseded
            if ($isRescheduling) {
                $case->hearings()->whereNull('superseded_at')->update([
                    'superseded_at' => now(),
                    'superseded_reason' => $reason,
                ]);

                $case->activeSummons()->update([
                    'superseded_at' => now(),
                    'void_note' => 'Superseded: '.$reason,
                ]);
            }

            $maxHearingSeq = (int) $case->hearings()->max('sequence_no');
            $maxSummonsSeq = (int) $case->summons()->max('sequence_no');
            $seqNo = max($maxHearingSeq, $maxSummonsSeq) + 1;

            // Resolve respondent party
            $respondent = ! empty($validated['respondent_case_party_id'])
                ? $case->caseParties()->where('id', $validated['respondent_case_party_id'])->first()
                : $case->caseParties()->where('role', 'respondent')->first();

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

            // 1. Create Hearing #N
            $hearing = Hearing::create([
                'case_id' => $case->id,
                'sequence_no' => $seqNo,
                'scheduled_at' => $scheduledCarbon,
                'venue' => $validated['venue'],
                'status' => 'scheduled',
                'presided_by_personnel_id' => $validated['presided_by'] ?? null,
            ]);

            // 2. Create Summons #N linked 1:1 with Hearing #N
            $summonsNumber = "SUMMONS-{$case->case_number}-#{$seqNo}";
            if (Summons::where('summons_number', $summonsNumber)->exists()) {
                $summonsNumber .= '-'.$hearing->id;
            }

            $summons = Summons::create([
                'case_id' => $case->id,
                'hearing_id' => $hearing->id,
                'respondent_case_party_id' => $respondent->id,
                'sequence_no' => $seqNo,
                'summons_number' => $summonsNumber,
                'issued_on' => now()->toDateString(),
                'issued_by' => $request->user()->id,
                'hearing_at' => $scheduledCarbon,
                'venue' => $validated['venue'],
                'remarks' => $validated['notes'] ?? ($reason ? "Reschedule reason: {$reason}" : null),
            ]);

            $hearing->update(['summons_id' => $summons->id]);

            // 3. Anchor first_meeting_on only if no earlier hearing date has already passed
            $earliestHearing = $case->hearings()->orderBy('sequence_no')->orderBy('id')->first();
            $anchorLocked = $earliestHearing
                && $earliestHearing->id !== $hearing->id
                && Carbon::parse($earliestHearing->scheduled_at)->startOfDay()->lte(Carbon::today());

            $caseUpdates = [
                'status' => CaseStatus::IN_MEDIATION,
                'notices_issued_at' => now(),
                'mediation_outcome' => null,
                'summons_service_status' => null,
                'summons_served_at' => null,
                'summons_served_by' => null,
                'summons_recipient_name' => null,
                'summons_recipient_relation' => null,
            ];

            if (! $anchorLocked) {
                $caseUpdates['first_meeting_on'] = $scheduledCarbon->toDateString();
            }

            $case->update($caseUpdates);

            // 4. Notification dispatch logging
            if (! empty($validated['notify_channels'])) {
                $complainant = $case->caseParties()->where('role', 'complainant')->with('party')->first();
                $complainantEmail = $complainant?->party?->email;

                CaseNote::create([
                    'case_id' => $case->id,
                    'user_id' => $request->user()->id,
                    'body' => $complainantEmail
                        ? "[Notification] Hearing #{$seqNo} & Summons #{$seqNo} notice sent via email to {$complainantEmail}. (No SMS configured.)"
                        : "[Notification] No email contact found for complainant. Tanod physical service required for Summons #{$seqNo}.",
                ]);
            }

            $noteBody = $isRescheduling
                ? "[Hearing #{$seqNo} & Summons #{$seqNo} Issued (Rescheduled)] Scheduled for {$scheduledCarbon->format('M d, Y g:i A')} at {$validated['venue']}. Reason: {$reason}."
                : "[Hearing #{$seqNo} & Summons #{$seqNo} Issued] Scheduled for {$scheduledCarbon->format('M d, Y g:i A')} at {$validated['venue']}.";
            if (! empty($validated['notes'])) {
                $noteBody .= ' '.$validated['notes'];
            }

            CaseNote::create([
                'case_id' => $case->id,
                'user_id' => $request->user()->id,
                'body' => $noteBody,
            ]);

            // 5. Audit Logs
            if ($isRescheduling) {
                $this->audit->log($request->user(), 'case.hearing_rescheduled', $case, [], [
                    'new_hearing_id' => $hearing->id,
                    'new_summons_id' => $summons->id,
                    'sequence_no' => $seqNo,
                    'reason' => $reason,
                    'scheduled_at' => $scheduledCarbon->toDateTimeString(),
                ]);
            }

            $this->audit->log($request->user(), 'case.hearing_and_summons_issued', $case, [], [
                'hearing_id' => $hearing->id,
                'summons_id' => $summons->id,
                'sequence_no' => $seqNo,
                'scheduled_at' => $scheduledCarbon->toDateTimeString(),
                'venue' => $validated['venue'],
            ]);
        });

        // Non-blocking warning banner if scheduled beyond 15 working days
        $clock = $this->kpService->calculateMediationClock($case->fresh());
        $redirect = redirect()->route('admin.cases.show', $case)
            ->with('success', "Hearing set for {$scheduledCarbon->format('M d, Y · g:i A')}. Summons #{$seqNo} generated. Print and serve it.");

        if (! empty($clock['end_date']) && $scheduledCarbon->startOfDay()->gt(Carbon::parse($clock['end_date']))) {
            $redirect->with('warning_message', 'Note: This hearing date is beyond the 15-working-day mediation period.');
        }

        return $redirect;
    }

    /**
     * Backwards-compatible alias for rescheduling.
     */
    public function rescheduleHearing(Request $request, BarangayCase $case): RedirectResponse
    {
        $request->merge(['reschedule_hearing_id' => $request->input('reschedule_hearing_id', 1)]);

        return $this->scheduleHearingAndIssueSummons($request, $case);
    }

    /**
     * Record summons service directly on the Summons record.
     */
    public function recordSummonsService(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'summons_id' => ['nullable', 'integer'],
            'served_at' => ['required', 'date', 'before_or_equal:today'],
            'service_method' => ['required', 'string', 'in:personal,substituted,refused,not_served,failed,appeared_voluntarily'],
            'served_by' => ['required', 'string', 'max:150'],
            'custom_served_by' => ['nullable', 'string', 'max:150'],
            'recipient_name' => ['nullable', 'string', 'max:150'],
            'recipient_relation' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $method = $validated['service_method'] === 'failed' ? 'not_served' : $validated['service_method'];

        if ($method === 'substituted' && (empty($validated['recipient_name']) || empty($validated['recipient_relation']))) {
            return back()->withInput()
                ->withErrors(['recipient_name' => 'Recipient name and relationship are required for Substituted Service.']);
        }

        $servedBy = ($validated['served_by'] === 'other' && ! empty($validated['custom_served_by']))
            ? $validated['custom_served_by']
            : $validated['served_by'];

        /** @var Summons|null $summons */
        $summons = ! empty($validated['summons_id'])
            ? $case->summons()->where('id', $validated['summons_id'])->first()
            : $case->activeSummons()->latest('sequence_no')->latest('id')->first();

        $serviceResult = match ($method) {
            'personal', 'appeared_voluntarily' => 'received',
            'substituted' => 'substituted',
            'refused' => 'refused',
            default => 'not_found',
        };

        $isServed = in_array($method, ['personal', 'substituted', 'refused', 'appeared_voluntarily'], true);

        if ($summons) {
            $summons->update([
                'served_on' => $validated['served_at'],
                'service_method' => $method,
                'served_by' => $servedBy,
                'received_by_name' => $validated['recipient_name'] ?? null,
                'received_by_relationship' => $validated['recipient_relation'] ?? null,
                'service_notes' => $validated['notes'] ?? null,
                'service_result' => $serviceResult,
                'remarks' => $validated['notes'] ?? $summons->remarks,
            ]);
        }

        $case->update([
            'summons_service_status' => $method,
            'summons_served_at' => $isServed ? $validated['served_at'] : null,
            'summons_served_by' => $servedBy,
            'summons_recipient_name' => $validated['recipient_name'] ?? null,
            'summons_recipient_relation' => $validated['recipient_relation'] ?? null,
        ]);

        CaseNote::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'body' => "[Summons Service - {$method}] By {$servedBy} on {$validated['served_at']}.".(! empty($validated['notes']) ? " {$validated['notes']}" : ''),
        ]);

        $this->audit->log($request->user(), 'case.summons_service_recorded', $case, [], [
            'summons_id' => $summons?->id,
            'service_method' => $method,
            'served_at' => $validated['served_at'],
            'served_by' => $servedBy,
        ]);

        $msg = $method === 'not_served'
            ? 'Summons recorded as could not be served. Schedule the next hearing to issue the next summons.'
            : 'Summons service recorded successfully.';

        return redirect()->route('admin.cases.show', $case)->with('success', $msg);
    }

    /**
     * Shortcut: Respondent appeared voluntarily without prior service.
     */
    public function recordVoluntaryAppearance(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'summons_id' => ['nullable', 'integer'],
        ]);

        /** @var Summons|null $summons */
        $summons = ! empty($validated['summons_id'])
            ? $case->summons()->where('id', $validated['summons_id'])->first()
            : $case->activeSummons()->latest('sequence_no')->latest('id')->first();

        $today = now()->toDateString();
        $userName = $request->user()->name;

        if ($summons) {
            $summons->update([
                'served_on' => $today,
                'service_method' => 'appeared_voluntarily',
                'served_by' => $userName,
                'service_result' => 'received',
                'service_notes' => 'Respondent appeared voluntarily at the barangay hall.',
            ]);
        }

        $case->update([
            'summons_service_status' => 'appeared_voluntarily',
            'summons_served_at' => $today,
            'summons_served_by' => $userName,
        ]);

        CaseNote::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'body' => "[Voluntary Appearance] Respondent appeared voluntarily; service step satisfied for Summons #{$summons?->sequence_no}.",
        ]);

        $this->audit->log($request->user(), 'case.summons_voluntary_appearance', $case, [], [
            'summons_id' => $summons?->id,
        ]);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Voluntary appearance recorded. Hearing outcome is now unlocked.');
    }

    /**
     * Override: Skip service with a mandatory reason, logged to audit trail.
     */
    public function skipSummonsService(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'summons_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        /** @var Summons|null $summons */
        $summons = ! empty($validated['summons_id'])
            ? $case->summons()->where('id', $validated['summons_id'])->first()
            : $case->activeSummons()->latest('sequence_no')->latest('id')->first();

        if ($summons) {
            $summons->update([
                'service_skipped_reason' => $validated['reason'],
                'override_note' => $validated['reason'],
            ]);
        }

        CaseNote::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'body' => "[Service Skipped (Override)] Reason: {$validated['reason']}",
        ]);

        $this->audit->log($request->user(), 'case.summons_service_skipped', $case, [], [
            'summons_id' => $summons?->id,
            'reason' => $validated['reason'],
        ]);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Service step skipped with audit reason. Hearing outcome is now unlocked.');
    }

    /**
     * Record hearing outcome on a Hearing record (requires service, voluntary appearance, or skip override).
     */
    public function recordHearingOutcome(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'hearing_id' => ['nullable', 'integer'],
            'outcome' => ['required', 'string', 'in:continued,settled,rescheduled,postponed,respondent_absent,complainant_absent,failed'],
            'complainant_present' => ['nullable', 'boolean'],
            'respondent_present' => ['nullable', 'boolean'],
            'both_parties_present' => ['nullable', 'boolean'],
            'hearing_held_on' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'early_endorse_reason' => ['nullable', 'string', 'max:500', 'required_if:outcome,failed'],
        ]);

        /** @var Hearing|null $hearing */
        $hearing = ! empty($validated['hearing_id'])
            ? $case->hearings()->where('id', $validated['hearing_id'])->first()
            : $case->hearings()->whereNull('superseded_at')->latest('sequence_no')->latest('id')->first();

        if ($hearing) {
            $linkedSummons = $hearing->summons ?? $case->activeSummons()->latest('id')->first();
            if ($linkedSummons && ! $linkedSummons->canRecordOutcome()) {
                return back()->with('error', 'Record service first (or use Skip service with a reason) before recording the hearing outcome.');
            }
        }

        $outcome = $validated['outcome'];
        $complainantAttendance = match (true) {
            $outcome === 'complainant_absent' => 'failed_to_appear',
            ! empty($validated['complainant_present']) || in_array($outcome, ['continued', 'settled', 'failed'], true) => 'appeared',
            default => 'failed_to_appear',
        };
        $respondentAttendance = match (true) {
            $outcome === 'respondent_absent' => 'failed_to_appear',
            ! empty($validated['respondent_present']) || in_array($outcome, ['continued', 'settled', 'failed'], true) => 'appeared',
            default => 'failed_to_appear',
        };

        $hearingStatus = match ($outcome) {
            'settled' => 'settled',
            'failed' => 'unsettled',
            'rescheduled', 'postponed' => 'reset',
            default => 'held',
        };

        if ($hearing) {
            $hearing->update([
                'status' => $hearingStatus,
                'complainant_attendance' => $complainantAttendance,
                'respondent_attendance' => $respondentAttendance,
                'outcome_note' => $validated['notes'] ?? $hearing->outcome_note,
            ]);
        }

        $previousStatus = $case->status;
        $caseUpdates = [
            'mediation_outcome' => $outcome,
        ];

        if ($outcome === 'settled') {
            $caseUpdates['status'] = CaseStatus::SETTLED;
        } elseif ($outcome === 'failed') {
            $caseUpdates['status'] = CaseStatus::UNSETTLED;
        } else {
            // Absent, postponed, or continued NEVER escalates; stays in mediation
            $caseUpdates['status'] = CaseStatus::IN_MEDIATION;
        }

        $case->update($caseUpdates);

        if ($previousStatus !== $caseUpdates['status']) {
            CaseStatusLog::create([
                'case_id' => $case->id,
                'user_id' => $request->user()->id,
                'from_status' => $previousStatus,
                'to_status' => $caseUpdates['status'],
                'note' => "Hearing outcome recorded as '{$outcome}'." . (! empty($validated['notes']) ? " {$validated['notes']}" : ''),
                'created_at' => now(),
            ]);
        }

        $noteParts = ["[Hearing Outcome: {$outcome}]"];
        if (! empty($validated['early_endorse_reason'])) {
            $noteParts[] = "Reason: {$validated['early_endorse_reason']}.";
        }
        if (! empty($validated['notes'])) {
            $noteParts[] = $validated['notes'];
        }

        CaseNote::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'body' => implode(' ', $noteParts),
        ]);

        $this->audit->log($request->user(), 'case.hearing_outcome_recorded', $case, [], [
            'hearing_id' => $hearing?->id,
            'outcome' => $outcome,
            'complainant_attendance' => $complainantAttendance,
            'respondent_attendance' => $respondentAttendance,
            'notes' => $validated['notes'] ?? null,
        ]);

        $msg = in_array($outcome, ['respondent_absent', 'complainant_absent', 'postponed', 'rescheduled'], true)
            ? 'Hearing outcome recorded. Case remains in mediation — schedule the next hearing when ready.'
            : "Mediation outcome recorded as '{$outcome}'.";

        return redirect()->route('admin.cases.show', $case)->with('success', $msg);
    }

    /**
     * Milestone 6: Mark signed settlement received.
     */
    public function markSignedSettlement(Request $request, BarangayCase $case): RedirectResponse
    {
        $maxUploadIni = ini_get('upload_max_filesize') ?: '2M';
        $validated = $request->validate([
            'settled_on' => ['required', 'date', 'before_or_equal:today'],
            'terms' => ['required', 'string', 'min:5'],
            'settlement_amount' => ['nullable', 'numeric', 'min:0'],
            'scan_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'terms.min' => 'Agreed settlement terms must be at least 5 characters.',
            'settled_on.before_or_equal' => 'Date signed cannot be in the future.',
            'scan_file.uploaded' => "The uploaded file exceeds the server upload limit ({$maxUploadIni}). Please upload a smaller PDF or image.",
        ]);

        $filePath = null;
        if ($request->hasFile('scan_file')) {
            $filePath = $request->file('scan_file')->store("case-files/{$case->id}", 'private');
        }

        $repudiationDays = (int) config('katarungang_pambarangay.repudiation_period_days', 10);
        $repudiationEndsAt = Carbon::parse($validated['settled_on'])->addDays($repudiationDays);
        $previousStatus = $case->status;

        $case->update([
            'settled_on' => $validated['settled_on'],
            'settlement_terms' => $validated['terms'],
            'settlement_amount' => $validated['settlement_amount'] ?? $case->settlement_amount,
            'settlement_agreement_path' => $filePath ?? $case->settlement_agreement_path,
            'repudiation_ends_at' => $repudiationEndsAt,
            'repudiated_at' => null,
            'repudiation_ground' => null,
            'repudiated_by' => null,
            'status' => CaseStatus::SETTLED,
        ]);

        CaseStatusLog::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'from_status' => $previousStatus,
            'to_status' => CaseStatus::SETTLED,
            'note' => "Signed Amicable Settlement (KP Form 16) received on {$validated['settled_on']}. 10-day repudiation window ends {$repudiationEndsAt->format('M d, Y')}.",
            'created_at' => now(),
        ]);

        CaseNote::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'body' => "[Signed Settlement Received (KP Form 16)] Signed on {$validated['settled_on']}. Terms: {$validated['terms']}",
        ]);

        $this->audit->log($request->user(), 'case.settlement_signed_received', $case, [], [
            'settled_on' => $validated['settled_on'],
            'repudiation_ends_at' => $repudiationEndsAt->toDateString(),
            'has_amount' => ! empty($validated['settlement_amount']),
            'has_file' => ! empty($filePath),
        ]);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', "Signed Amicable Settlement recorded. 10-day repudiation period active until {$repudiationEndsAt->format('M d, Y')}.");
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
            'explanation' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $filePath = null;
        if ($request->hasFile('sworn_statement_file')) {
            $filePath = $request->file('sworn_statement_file')->store("case-files/{$case->id}", 'private');
        }

        $previousStatus = $case->status;

        $case->update([
            'repudiated_at' => $validated['repudiation_date'],
            'repudiation_ground' => $validated['ground'],
            'repudiated_by' => $validated['repudiated_by'],
            'repudiation_document_path' => $filePath ?? $case->repudiation_document_path,
            'status' => CaseStatus::UNSETTLED,
        ]);

        CaseStatusLog::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'from_status' => $previousStatus,
            'to_status' => CaseStatus::UNSETTLED,
            'note' => "Settlement repudiated by {$validated['repudiated_by']} on ground of {$validated['ground']}.",
            'created_at' => now(),
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
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $case->update([
            'cfa_issued_at' => $validated['issued_at'],
            'status' => CaseStatus::ESCALATED,
        ]);

        CaseNote::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'body' => "[CFA Issued] Reason: {$validated['reason']}",
        ]);

        $this->audit->log($request->user(), 'case.cfa_issued', $case, [], [
            'issued_at' => $validated['issued_at'],
            'reason' => $validated['reason'],
        ]);

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Certificate to File Action (KP Form 20) issued and logged.');
    }
}
