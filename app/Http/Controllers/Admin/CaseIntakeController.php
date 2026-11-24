<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\CaseParty;
use App\Models\CaseType;
use App\Models\ComplaintIntake;
use App\Models\Party;
use App\Models\Purok;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FiscalYearService;
use App\Services\GeminiService;
use App\Services\NumberingService;
use App\Support\CaseStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CaseIntakeController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected FiscalYearService $fiscalYearService,
        protected NumberingService $numberingService,
        protected GeminiService $gemini
    ) {}

    /**
     * Display full details of a pending citizen complaint intake with AI suggestions.
     */
    public function show(ComplaintIntake $intake): View
    {
        $caseTypes = CaseType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $puroks = Purok::query()->orderBy('name')->get();
        $staffUsers = User::query()->where('is_active', true)->orderBy('name')->get();

        $fy = $this->fiscalYearService->forNewRecord();
        $blotterPreview = $this->numberingService->preview('blotter', $fy);
        $caseNumberPreview = $this->numberingService->preview('case', $fy);

        return view('admin.cases.intakes.show', [
            'intake' => $intake,
            'caseTypes' => $caseTypes,
            'puroks' => $puroks,
            'staffUsers' => $staffUsers,
            'blotterPreview' => $blotterPreview,
            'caseNumberPreview' => $caseNumberPreview,
        ]);
    }

    /**
     * Re-trigger AI extraction for this complaint intake.
     */
    public function retryAi(ComplaintIntake $intake): RedirectResponse
    {
        $this->gemini->analyzeComplaint($intake);

        return back()->with('status', 'AI analysis was re-triggered for this complaint intake.');
    }

    /**
     * Docket the complaint intake as an official Katarungang Pambarangay case.
     */
    public function docket(Request $request, ComplaintIntake $intake): RedirectResponse
    {
        $validated = $request->validate([
            'case_type_id' => ['required', 'exists:case_types,id'],
            'purok_id' => ['nullable', 'exists:puroks,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'what_happened' => ['required', 'string', 'min:5', 'max:5000'],
            'who_involved' => ['nullable', 'string', 'max:1000'],
            'when_happened' => ['nullable', 'string', 'max:255'],
            'where_happened' => ['nullable', 'string', 'max:1000'],
            'why_how_happened' => ['nullable', 'string', 'max:5000'],
            'rights_violated' => ['nullable', 'string', 'max:3000'],
            'relief_requested' => ['nullable', 'string', 'max:3000'],
            'field_provenance' => ['nullable', 'array'],
            'kp_eligibility' => ['required', 'array'],
            'kp_eligibility.same_barangay' => ['required'],
            'kp_eligibility.kp_matter' => ['required'],
            'kp_eligibility.non_confidential' => ['required'],
            'kp_eligibility.residency_verified' => ['required'],
            'is_confidential' => ['nullable', 'boolean'],
            'message_to_complainant' => ['nullable', 'string', 'max:500'],
        ], [
            'kp_eligibility.same_barangay.required' => 'KP eligibility requires confirming both parties reside in Barangay Calarian or adjacent areas.',
            'kp_eligibility.kp_matter.required' => 'KP eligibility requires confirming the dispute falls under Lupon jurisdiction.',
            'kp_eligibility.non_confidential.required' => 'Disputes involving VAWC or child protection cannot be docketed under KP.',
            'kp_eligibility.residency_verified.required' => 'Please confirm complainant residency verification before docketing.',
        ]);

        $case = DB::transaction(function () use ($validated, $request, $intake) {
            $fy = $this->fiscalYearService->forNewRecord();
            $caseNumber = $this->numberingService->next('case', $fy);
            $caseType = CaseType::find($validated['case_type_id']);

            $title = "{$caseType->name}: {$intake->complainant_name} vs. {$intake->respondent_name}";

            $narrativeSections = [];
            $narrativeSections[] = $validated['what_happened'];
            if (! empty($validated['why_how_happened'])) {
                $narrativeSections[] = "Circumstances:\n".$validated['why_how_happened'];
            }
            if (! empty($validated['rights_violated'])) {
                $narrativeSections[] = "Rights Violated:\n".$validated['rights_violated'];
            }
            if (! empty($validated['relief_requested'])) {
                $narrativeSections[] = "Relief Requested:\n".$validated['relief_requested'];
            }
            $fullNarrative = implode("\n\n", $narrativeSections);

            $incidentDate = null;
            $parsedWhenHappened = null;
            if (! empty($validated['when_happened']) && $validated['when_happened'] !== 'Not specified in narrative') {
                $ts = strtotime($validated['when_happened']);
                if ($ts !== false && $ts > 0) {
                    $incidentDate = date('Y-m-d', $ts);
                    $parsedWhenHappened = date('Y-m-d H:i:s', $ts);
                }
            }

            $case = BarangayCase::create([
                'case_number' => $caseNumber,
                'fiscal_year_id' => $fy->id,
                'case_type_id' => $caseType->id,
                'source' => 'complaint_intake',
                'title' => substr($title, 0, 150),
                'narrative' => $fullNarrative,
                'filed_on' => now()->toDateString(),
                'incident_date' => $incidentDate,
                'incident_location' => $validated['where_happened'] ?? 'Barangay Calarian',
                'purok_id' => $validated['purok_id'] ?? null,
                'status' => CaseStatus::FILED,
                'encoded_by' => $request->user()->id,
                'assigned_to' => $validated['assigned_to'] ?? null,
                'is_confidential' => (bool) ($validated['is_confidential'] ?? false),
                'eligibility_checklist' => $validated['kp_eligibility'],
            ]);

            // Create or match complainant party
            $complainantParty = Party::create([
                'full_name' => $intake->complainant_name,
                'alias' => $intake->complainant_alias,
                'contact_number' => $intake->complainant_contact,
                'address_line' => $intake->complainant_address,
                'residency_status' => $intake->complainant_residency_claim ?? 'resident',
                'purok_id' => $validated['purok_id'] ?? null,
            ]);

            CaseParty::create([
                'case_id' => $case->id,
                'party_id' => $complainantParty->id,
                'role' => 'complainant',
                'residency_at_filing' => $complainantParty->residency_status,
            ]);

            // Create or match respondent party
            $respondentParty = Party::create([
                'full_name' => $intake->respondent_name,
                'alias' => $intake->respondent_alias,
                'contact_number' => $intake->respondent_contact,
                'address_line' => $intake->respondent_address,
                'residency_status' => 'resident',
                'purok_id' => $validated['purok_id'] ?? null,
            ]);

            CaseParty::create([
                'case_id' => $case->id,
                'party_id' => $respondentParty->id,
                'role' => 'respondent',
                'residency_at_filing' => $respondentParty->residency_status,
            ]);

            // Mark intake as docketed with final reviewed values
            $intake->update([
                'status' => 'docketed',
                'case_id' => $case->id,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'what_happened' => $validated['what_happened'],
                'who_involved' => $validated['who_involved'] ?? $intake->who_involved,
                'when_happened' => $parsedWhenHappened ?? $intake->when_happened,
                'where_happened' => $validated['where_happened'] ?? $intake->where_happened,
                'why_how_happened' => $validated['why_how_happened'] ?? $intake->why_how_happened,
                'rights_violated' => $validated['rights_violated'] ?? $intake->rights_violated,
                'relief_requested' => $validated['relief_requested'] ?? $intake->relief_requested,
                'field_provenance' => $validated['field_provenance'] ?? $intake->field_provenance,
            ]);

            $this->audit->log(
                by: $request->user(),
                action: 'complaint_intake.docketed',
                subject: $intake,
                old: ['status' => 'pending'],
                new: ['status' => 'docketed', 'case_id' => $case->id, 'case_number' => $case->case_number]
            );

            return $case;
        });

        return redirect()->route('admin.cases.show', $case)
            ->with('success', "Case {$case->case_number} docketed. Next: print the complaint for signing.");
    }

    /**
     * Reject or redirect a citizen complaint intake with a mandatory reason.
     */
    public function reject(Request $request, ComplaintIntake $intake): RedirectResponse
    {
        $validated = $request->validate([
            'outcome_reason' => ['required', 'string', 'max:50'],
            'explanation_note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $fullReason = $validated['outcome_reason'].': '.$validated['explanation_note'];

        $oldStatus = $intake->status;
        $intake->update([
            'status' => 'rejected',
            'outcome_reason' => $fullReason,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->audit->log(
            by: $request->user(),
            action: 'complaint_intake.rejected',
            subject: $intake,
            old: ['status' => $oldStatus],
            new: ['status' => 'rejected', 'reason' => $fullReason]
        );

        return redirect()->route('admin.cases.index', ['tab' => 'pending_intake'])
            ->with('status', 'Complaint intake request was successfully rejected.');
    }
}
