<?php

namespace App\Services;

use App\Mail\ReferralUpdateMail;
use App\Models\AuditLog;
use App\Models\BarangayCase;
use App\Models\EscalationDestination;
use App\Models\Office;
use App\Models\Referral;
use App\Models\ReferralAttachment;
use App\Models\ReferralUpdate;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Referral tracking for reports beyond barangay authority.
 * Agencies have no accounts: the barangay admin logs every update.
 */
class ReferralService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ReportStatusService $statusService
    ) {}

    /**
     * Create (or update) the referral for a report, then move the report to
     * "referred" through ReportStatusService. Runs inside one transaction.
     */
    public function prepareReferral(
        Report $report,
        Office $office,
        ?User $user,
        ?string $letterText = null,
        ?string $residentMessage = null,
        ?int $followUpOwnerId = null
    ): Referral {
        return DB::transaction(function () use ($report, $office, $user, $letterText, $residentMessage, $followUpOwnerId): Referral {
            $lockedReport = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            $referral = Referral::query()
                ->where('report_id', $lockedReport->id)
                ->where('office_id', $office->id)
                ->first();

            $letter = $letterText !== null && trim($letterText) !== ''
                ? $letterText
                : $this->buildLetter($lockedReport, $office);

            $ownerId = $followUpOwnerId ?? $user?->id;

            if ($referral === null) {
                $fy = $lockedReport->fiscalYear ?? app(FiscalYearService::class)->active();
                $referral = Referral::query()->create([
                    'fiscal_year_id' => $fy?->id,
                    'report_id' => $lockedReport->id,
                    'office_id' => $office->id,
                    'reference_no' => $this->generateReferenceNo(),
                    'referral_text' => $letter,
                    'status' => 'prepared',
                    'prepared_by' => $user?->id,
                    'follow_up_owner_id' => $ownerId,
                ]);
            } else {
                $referral->update([
                    'referral_text' => $letter,
                    'status' => 'prepared',
                    'prepared_by' => $user?->id ?? $referral->prepared_by,
                    'follow_up_owner_id' => $ownerId ?? $referral->follow_up_owner_id ?? $referral->prepared_by,
                    'closed_at' => null,
                    'outcome' => null,
                ]);
            }

            $note = $residentMessage !== null && trim($residentMessage) !== ''
                ? $residentMessage
                : 'We referred this report to '.$office->name.'.';

            if ($lockedReport->status !== 'referred') {
                $this->statusService->transition($lockedReport, 'referred', $note, $user);
            } else {
                $lockedReport->statusLogs()->create([
                    'user_id' => $user?->id,
                    'from_status' => 'referred',
                    'to_status' => 'referred',
                    'note' => 'Referral updated: '.$referral->reference_no.' for '.$office->name.'.',
                ]);
            }

            return $referral->refresh();
        });
    }

    /** Mark the referral as sent and schedule the follow-up date. */
    public function markSent(Referral $referral, User $user, ?string $sentAt = null, ?string $sentVia = null, ?string $followUpAt = null): Referral
    {
        $sentDate = $sentAt ? Carbon::parse($sentAt) : now();
        $via = in_array($sentVia, array_keys(Referral::SENT_VIA), true) ? $sentVia : null;
        $followUpDate = $followUpAt
            ? Carbon::parse($followUpAt)
            : $sentDate->copy()->addDays((int) (setting('referral.follow_up_days') ?? Setting::value('referral_follow_up_days') ?? config('reports.referrals.follow_up_days', 3)));

        DB::transaction(function () use ($referral, $user, $sentDate, $via, $followUpDate): void {
            $referral->update([
                'sent_at' => $sentDate,
                'sent_via' => $via,
                'follow_up_at' => $followUpDate,
                'status' => 'sent',
            ]);

            $referral->report->statusLogs()->create([
                'user_id' => $user->id,
                'from_status' => $referral->report->status,
                'to_status' => $referral->report->status,
                'note' => 'Referral '.$referral->reference_no.' marked as sent to '.$referral->office->name
                    .($via ? ' via '.$via : '').'. Follow up on '.$followUpDate->format('M d, Y').'.',
            ]);
        });

        $this->notifyResident(
            $referral->report,
            'Your report was referred to '.$referral->office->name,
            'Your report '.$referral->report->tracking_code.' was referred to '.$referral->office->name
                .' on '.$sentDate->format('M d, Y').'. We will keep you updated here.',
            'referral_sent'
        );

        return $referral->refresh();
    }

    /**
     * Log an agency update (phone call, email reply, site visit, ...) and
     * optionally move the referral status forward (acknowledged, action_taken).
     */
    public function logUpdate(
        Referral $referral,
        User $user,
        string $note,
        string $channel,
        ?string $occurredAt = null,
        ?string $photoPath = null,
        ?string $agencyReferenceNo = null,
        ?string $newStatus = null,
        bool $visibleToResident = true
    ): ReferralUpdate {
        $update = DB::transaction(function () use ($referral, $user, $note, $channel, $occurredAt, $photoPath, $agencyReferenceNo, $newStatus, $visibleToResident): ReferralUpdate {
            $created = $referral->updates()->create([
                'user_id' => $user->id,
                'note' => $note,
                'channel' => $channel,
                'occurred_at' => $occurredAt ? Carbon::parse($occurredAt) : now(),
                'photo_path' => $photoPath,
                'visible_to_resident' => $visibleToResident,
            ]);

            $attributes = [];
            if ($agencyReferenceNo !== null && trim($agencyReferenceNo) !== '') {
                $attributes['agency_reference_no'] = trim($agencyReferenceNo);
            }
            if ($newStatus !== null && in_array($newStatus, Referral::UPDATE_TARGET_STATUSES, true) && $referral->isOpen()) {
                $attributes['status'] = $newStatus;
            }
            if ($attributes !== []) {
                $referral->update($attributes);
            }

            // Keep internal-only update text out of the resident-visible timeline.
            $timelineNote = $visibleToResident
                ? 'Referral update for '.$referral->reference_no.' ('.$referral->status.'): '.Str::limit($note, 120)
                : 'Internal referral update logged for '.$referral->reference_no.' ('.$referral->status.'). Details are not shown to the resident.';

            $referral->report->statusLogs()->create([
                'user_id' => $user->id,
                'from_status' => $referral->report->status,
                'to_status' => $referral->report->status,
                'note' => $timelineNote,
            ]);

            return $created;
        });

        if ($update->visible_to_resident) {
            $this->notifyResident(
                $referral->report,
                'Update on your report from '.$referral->office->name,
                'Update on report '.$referral->report->tracking_code.' ('.$referral->office->name.'): '.Str::limit($note, 200),
                'referral_update'
            );
        }

        return $update;
    }

    /**
     * Close the referral. A resolved agency outcome moves a referred report to
     * in_progress for barangay staff verification and proof before resolution;
     * declined or no_response keeps the report open for the next action.
     */
    public function close(Referral $referral, User $user, string $outcome, string $note): Referral
    {
        $movedToInProgress = DB::transaction(function () use ($referral, $user, $outcome, $note): bool {
            $attributes = [
                'status' => 'closed',
                'closed_at' => now(),
                'outcome' => $outcome,
            ];
            if ($referral->sent_at === null) {
                $attributes['follow_up_at'] = null;
            }
            $referral->update($attributes);

            $referral->updates()->create([
                'user_id' => $user->id,
                'note' => 'Referral closed ('.$outcome.'): '.$note,
                'channel' => 'other',
                'visible_to_resident' => $outcome === 'resolved',
            ]);

            if ($outcome === 'resolved' && $this->statusService->canTransition($referral->report->status, 'in_progress')) {
                $this->statusService->transition(
                    $referral->report,
                    'in_progress',
                    'Agency reported the referred issue as resolved; awaiting barangay verification and proof: '.Str::limit($note, 150),
                    $user
                );

                return true;
            }

            $referral->report->statusLogs()->create([
                'user_id' => $user->id,
                'from_status' => $referral->report->status,
                'to_status' => $referral->report->status,
                'note' => 'Referral '.$referral->reference_no.' closed ('.$outcome.'). Report stays open; choose the next action.',
            ]);

            return false;
        });

        if ($movedToInProgress) {
            $this->notifyResident(
                $referral->report,
                'Agency completed work on your report',
                'The agency handling report '.$referral->report->tracking_code.' reported the issue as completed. Barangay staff will verify and upload proof before final confirmation.',
                'referral_closed'
            );
        }

        return $referral->refresh();
    }

    /** REF-YYYY-NNNN, unique, sequential per year via NumberingService. */
    public function generateReferenceNo(): string
    {
        return app(NumberingService::class)->next('referral');
    }

    /**
     * Referrals with follow_up_at <= today and status not closed.
     *
     * @return Collection<int, Referral>
     */
    public function dueForFollowUp(): Collection
    {
        return Referral::query()
            ->with(['office', 'report.category', 'report.purok', 'case'])
            ->where('status', '!=', 'closed')
            ->whereNotNull('follow_up_at')
            ->whereDate('follow_up_at', '<=', today())
            ->orderBy('follow_up_at')
            ->get();
    }

    /**
     * Case-to-referral handoff for escalated cases.
     */
    public function createFromCase(BarangayCase $case, EscalationDestination $destination, User $by): Referral
    {
        return DB::transaction(function () use ($case, $destination, $by): Referral {
            $office = $destination->office;
            if (! $office) {
                $office = Office::firstOrCreate(
                    ['name' => $destination->name],
                    ['type' => 'external', 'is_external' => true, 'is_active' => true]
                );
            }

            $fy = $case->fiscalYear ?? app(FiscalYearService::class)->active();
            $refNo = $this->generateReferenceNo();

            $complainantsText = $case->complainants->pluck('full_name')->implode(', ') ?: 'Complainant';
            $respondentsText = $case->respondents->pluck('full_name')->implode(', ') ?: 'Respondent';
            $reason = $case->escalations()->latest()->value('reason') ?? 'Dispute reached an impasse before the Lupong Tagapamayapa.';

            $letterText = "CASE REFERRAL & ENDORSEMENT\n\n"
                ."Case Docket No.: {$case->case_number}\n"
                ."Title: {$case->title}\n"
                ."Transmitted to: {$destination->name}\n\n"
                ."Parties:\n"
                ."- Complainant(s): {$complainantsText}\n"
                ."- Respondent(s): {$respondentsText}\n\n"
                ."Impasse / Escalation Grounds:\n{$reason}\n\n"
                .'Case documents and CFA certification are attached to this referral.';

            $followUpDays = (int) Setting::value('referral_follow_up_days', config('reports.referrals.follow_up_days', 3));

            $referral = Referral::create([
                'fiscal_year_id' => $fy?->id,
                'case_id' => $case->id,
                'report_id' => $case->source_report_id,
                'office_id' => $office->id,
                'reference_no' => $refNo,
                'referral_text' => $letterText,
                'status' => 'sent',
                'sent_at' => now(),
                'sent_via' => 'portal',
                'follow_up_at' => now()->addDays($followUpDays),
                'prepared_by' => $by->id,
                'follow_up_owner_id' => $by->id,
            ]);

            $attachmentContent = "BARANGAY CALARIAN - OFFICIAL CASE DOSSIER\n"
                ."===========================================\n"
                ."Case No.: {$case->case_number}\n"
                ."Title: {$case->title}\n"
                .'Filing Date: '.($case->filed_on?->format('F d, Y') ?? 'N/A')."\n"
                ."Narrative:\n{$case->narrative}\n\n"
                ."Complainant(s): {$complainantsText}\n"
                ."Respondent(s): {$respondentsText}\n"
                ."Destination Agency: {$destination->name}\n"
                ."Escalation Grounds: {$reason}\n";

            $attachmentPath = "referral-attachments/{$referral->id}/case-{$case->case_number}-dossier.txt";
            Storage::disk('private')->put($attachmentPath, $attachmentContent);

            ReferralAttachment::create([
                'referral_id' => $referral->id,
                'label' => "Case Dossier & Summary (#{$case->case_number})",
                'path' => $attachmentPath,
                'uploaded_by' => $by->id,
            ]);

            ReferralUpdate::create([
                'referral_id' => $referral->id,
                'user_id' => $by->id,
                'note' => "Case {$case->case_number} officially transmitted to {$destination->name} portal by {$by->name}.",
                'channel' => 'portal',
                'occurred_at' => now(),
                'visible_to_resident' => true,
            ]);

            AuditLog::create([
                'user_id' => $by->id,
                'action' => 'referral.created_from_case',
                'model' => Referral::class,
                'model_id' => $referral->id,
                'old_values' => null,
                'new_values' => [
                    'case_id' => $case->id,
                    'case_number' => $case->case_number,
                    'reference_no' => $refNo,
                    'destination' => $destination->name,
                ],
            ]);

            return $referral;
        });
    }

    /**
     * Build the referral letter from the stored template (settings key
     * referral_letter_template) with sensible defaults.
     */
    public function buildLetter(Report $report, Office $office, ?string $subject = null): string
    {
        $report->loadMissing(['category', 'purok']);
        $categoryName = $report->category?->name ?? 'Not assigned';
        $mapsLink = $report->lat !== null && $report->lng !== null
            ? 'https://www.google.com/maps/search/?api=1&query='.$report->lat.','.$report->lng
            : 'Not available';
        $photoLinks = $report->photos->isNotEmpty()
            ? $report->photos
                ->map(fn ($photo): string => route('admin.reports.photos.show', [$report, $photo]))
                ->implode("\n")
            : ($report->photo_path ? route('admin.reports.photo', $report) : 'None attached');
        $barangayName = (string) Setting::value('barangay_name', 'Barangay Calarian');
        $captainName = (string) Setting::value('barangay_captain_name', 'The Barangay Captain');
        $barangayContact = (string) Setting::value('barangay_contact_details', 'Barangay Hall, Calarian, Zamboanga City');

        $location = trim(($report->purok?->name ?? 'Not specified')
            .(($report->landmark ?? '') !== '' ? ', near '.$report->landmark : ''));
        $coordinates = $report->lat !== null && $report->lng !== null
            ? $report->lat.', '.$report->lng
            : 'Not available';

        $tokens = [
            'reference_no' => $report->activeReferral?->reference_no ?? 'Assigned on send',
            'date' => now()->format('F j, Y'),
            'agency_name' => $office->name,
            'agency_head' => $office->contact_person ?? 'The Office Head',
            'agency_address' => $office->address ?? 'Zamboanga City',
            'subject' => $subject ?? 'Referral of report '.$report->tracking_code.' ('.$categoryName.')',
            'tracking_code' => $report->tracking_code,
            'category' => $categoryName,
            'priority' => $report->urgency ?? 'Not set',
            'date_reported' => $report->created_at->format('M d, Y'),
            'location' => $location,
            'landmark' => $report->landmark !== null && $report->landmark !== '' ? $report->landmark : 'Not specified',
            'purok' => $report->purok?->name ?? 'Not specified',
            'coordinates' => $coordinates,
            'maps_link' => $mapsLink,
            'final_description' => $report->staff_description ?? $report->description,
            'photo_links' => $photoLinks,
            'barangay_name' => $barangayName,
            'sign_off' => $captainName,
            'barangay_contact' => $barangayContact,
        ];

        $wrappedTokens = [];
        foreach ($tokens as $key => $val) {
            $wrappedTokens['{'.$key.'}'] = (string) $val;
            $wrappedTokens[$key] = (string) $val;
        }

        return strtr((string) Setting::value('referral_letter_template', $this->defaultLetterTemplate()), $wrappedTokens);
    }

    public function defaultLetterTemplate(): string
    {
        return <<<'TPL'
        {reference_no}
        {date}

        {agency_name}
        Attention: {agency_head}
        {agency_address}

        Subject: {subject}

        Dear {agency_head},

        The {barangay_name} received a public concern report ({tracking_code}) that appears to be beyond barangay authority and under your office's mandate. We respectfully refer the details below for your appropriate action.

        Tracking code: {tracking_code}
        Category: {category}
        Priority: {priority}
        Date reported: {date_reported}
        Location: {location}
        Coordinates: {coordinates}
        Map: {maps_link}
        Photos: {photo_links}

        Report details:
        {final_description}

        We would appreciate it if your office could acknowledge receipt of this referral and send us updates on the action taken, so we can keep the reporting resident informed.

        Sincerely,

        {sign_off}
        {barangay_name}
        {barangay_contact}
        TPL;
    }

    /**
     * Notify the resident by email when the referral is sent or updated.
     * Wrapped in try/catch; a mail failure never breaks the request.
     */
    private function notifyResident(Report $report, string $subject, string $line, string $type): void
    {
        if ($report->is_anonymous || ! $report->reporter_email) {
            return;
        }

        try {
            Mail::to($report->reporter_email)->send(new ReferralUpdateMail($report, $subject, $line));
            $this->notificationService->log($report, $type, $report->reporter_email);
        } catch (Throwable $exception) {
            Log::warning('Referral resident notification failed.', [
                'report_id' => $report->id,
                'type' => $type,
                'exception' => $exception::class,
            ]);
            $this->notificationService->log($report, $type, (string) $report->reporter_email, 'failed', $exception::class);
        }
    }

    /** Log a staff override of the rule-based jurisdiction decision (override-only). */
    public function logJurisdictionOverride(Report $report, User $user, string $ruleMode, string $chosenMode): void
    {
        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'jurisdiction_mode_override',
            'model' => Report::class,
            'model_id' => $report->id,
            'old_values' => ['within_authority' => $ruleMode === 'within'],
            'new_values' => ['within_authority' => $chosenMode === 'within'],
        ]);

        $report->statusLogs()->create([
            'user_id' => $user->id,
            'from_status' => $report->status,
            'to_status' => $report->status,
            'note' => 'Jurisdiction override: routing rule says '.$ruleMode.'; staff chose '.$chosenMode.'.',
        ]);
    }
}
