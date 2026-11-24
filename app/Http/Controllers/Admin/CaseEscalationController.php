<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\CaseEscalation;
use App\Models\EscalationDestination;
use App\Models\Personnel;
use App\Services\AuditLogger;
use App\Services\CaseStatusService;
use App\Services\NumberingService;
use App\Services\OverdueService;
use App\Services\ReferralService;
use App\Services\SignatoryResolver;
use App\Support\CaseStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CaseEscalationController extends Controller
{
    public function __construct(
        protected CaseStatusService $statusService,
        protected OverdueService $overdueService,
        protected NumberingService $numberingService,
        protected SignatoryResolver $signatoryResolver,
        protected AuditLogger $auditLogger
    ) {}

    public function create(Request $request, BarangayCase $case): View
    {
        $overdue = $this->overdueService->forCase($case);
        $hasSoftWarning = (! $overdue['is_overdue'] && ($overdue['day'] ?? 0) < 15);
        $warningMessage = $hasSoftWarning
            ? "15 working days have not yet elapsed (Day {$overdue['day']} of 15). Mediation or conciliation period is still active. Complainant may request escalation early, and authorized officials decide whether to issue CFA."
            : null;

        $hasNoPangkatWarning = empty($case->endorsed_to_pangkat_on) && empty($case->pangkat_chairman_personnel_id);

        $complainants = $case->caseParties()
            ->where('role', 'complainant')
            ->with('party')
            ->get();

        $destinations = EscalationDestination::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $personnel = Personnel::query()
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        return view('admin.case-actions.escalation.create', [
            'case' => $case,
            'overdue' => $overdue,
            'hasSoftWarning' => $hasSoftWarning,
            'warningMessage' => $warningMessage,
            'hasNoPangkatWarning' => $hasNoPangkatWarning,
            'complainants' => $complainants,
            'destinations' => $destinations,
            'personnel' => $personnel,
        ]);
    }

    public function store(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'requested_by_case_party_id' => [
                'required',
                'integer',
                Rule::exists('case_parties', 'id')->where('case_id', $case->id),
            ],
            'requested_on' => ['required', 'date'],
            'escalation_destination_id' => ['required', 'integer', Rule::exists('escalation_destinations', 'id')],
            'reason' => ['required', 'string', 'min:5'],
            'decided_by_personnel_id' => ['nullable', 'integer', Rule::exists('personnel', 'id')],
            'signed_by_personnel_id' => ['nullable', 'integer', Rule::exists('personnel', 'id')],
            'override_note' => ['nullable', 'string'],
        ]);

        $destination = EscalationDestination::findOrFail($validated['escalation_destination_id']);

        try {
            $cfaNumber = $this->numberingService->next('cfa');
        } catch (\Throwable) {
            $cfaNumber = 'CFA-'.date('Y').'-'.$case->case_number;
        }

        $summonsCount = $case->activeSummons()->count();

        $escalation = CaseEscalation::create([
            'case_id' => $case->id,
            'requested_by_case_party_id' => $validated['requested_by_case_party_id'],
            'requested_on' => $validated['requested_on'],
            'escalation_destination_id' => $destination->id,
            'escalated_by' => $request->user()->id,
            'decided_by_personnel_id' => $validated['decided_by_personnel_id'] ?? null,
            'cfa_number' => $cfaNumber,
            'signed_by_personnel_id' => $validated['signed_by_personnel_id'] ?? null,
            'reason' => $validated['reason'],
            'override_note' => $validated['override_note'] ?? null,
            'summons_count_at_time' => $summonsCount,
            'escalated_at' => now(),
        ]);

        if (config('features.case_referral_handoff')) {
            try {
                $referral = app(ReferralService::class)->createFromCase($case, $destination, $request->user());
                $escalation->update(['referral_id' => $referral->id]);
            } catch (\Throwable $e) {
                // If handoff fails or is stubbed, log error
            }
        }

        if ($case->status !== CaseStatus::ESCALATED) {
            $this->statusService->transition(
                $case,
                CaseStatus::ESCALATED,
                "Case escalated to {$destination->name}: ".Str::limit($validated['reason'], 120),
                $request->user()
            );
        }

        $this->auditLogger->log(
            $request->user(),
            'case.escalated',
            $case,
            [],
            [
                'escalation_id' => $escalation->id,
                'cfa_number' => $cfaNumber,
                'destination' => $destination->name,
                'summons_count' => $summonsCount,
            ]
        );

        return redirect()->route('admin.cases.show', ['case' => $case, 'tab' => 'escalation'])
            ->with('success', "Case escalated to {$destination->name}. CFA #{$cfaNumber} issued.");
    }

    public function cfa(Request $request, BarangayCase $case): View
    {
        $case->loadMissing(['caseParties.party', 'pangkatChairman', 'escalations.destination', 'escalations.requestedByParty.party']);
        $escalation = $case->escalations()->latest()->first();

        $signatories = $this->signatoryResolver->forDocument('certificate_to_file_action', now(), $case);

        return view('admin.case-actions.escalation.cfa', [
            'case' => $case,
            'escalation' => $escalation,
            'signatories' => $signatories,
        ]);
    }

    public function letter(Request $request, BarangayCase $case): View
    {
        if ($request->query('type') === 'cfa') {
            return $this->cfa($request, $case);
        }

        $case->loadMissing(['caseParties.party', 'escalations.destination', 'escalations.requestedByParty.party']);
        $escalation = $case->escalations()->latest()->first();

        $signatories = $this->signatoryResolver->forDocument('case_endorsement', now(), $case);

        return view('admin.case-actions.escalation.endorsement-letter', [
            'case' => $case,
            'escalation' => $escalation,
            'signatories' => $signatories,
        ]);
    }
}
