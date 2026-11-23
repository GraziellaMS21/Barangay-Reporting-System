<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\Summons;
use App\Models\User;
use App\Services\CaseActions\IssueSummonsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SummonsController extends Controller
{
    public function __construct(
        private IssueSummonsService $issueSummonsService
    ) {}

    public function create(BarangayCase $case): View
    {
        return view('admin.case-actions.summons.create', [
            'case' => $case,
            'canIssueSummons' => $this->issueSummonsService->canIssue($case),
            'respondents' => $case->caseParties()
                ->where('role', 'respondent')
                ->with('party')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'respondent_case_party_id' => [
                'required',
                'integer',
                Rule::exists('case_parties', 'id')
                    ->where('case_id', $case->id)
                    ->where('role', 'respondent'),
            ],
            'hearing_at' => ['required', 'date', 'after:now'],
            'venue' => ['required', 'string', 'max:255'],
        ]);

        if (! $this->issueSummonsService->canIssue($case)) {
            throw ValidationException::withMessages([
                'case' => 'Summons may only be issued for active formal complaints.',
            ]);
        }

        $issuer = $request->user();
        abort_unless($issuer instanceof User, 403);

        $summons = $this->issueSummonsService->issue($case, $issuer, $validated);

        return to_route('admin.cases.show', $case)
            ->with('success', "Summons {$summons->summons_number} issued.");
    }

    public function service(Request $request, BarangayCase $case, Summons $summons): RedirectResponse
    {
        $validated = $request->validate([
            'service_result' => ['required', 'string', Rule::in(['received', 'refused', 'not_found', 'other'])],
            'served_on' => ['required', 'date'],
            'served_by_personnel_id' => ['nullable', 'integer', Rule::exists('personnel', 'id')],
            'remarks' => ['nullable', 'string'],
        ]);

        $summons->update([
            'service_result' => $validated['service_result'],
            'served_on' => $validated['served_on'],
            'served_by_personnel_id' => $validated['served_by_personnel_id'] ?? $summons->served_by_personnel_id,
            'remarks' => $validated['remarks'] ?? $summons->remarks,
        ]);

        return to_route('admin.cases.show', $case)
            ->with('success', "Service result recorded for summons {$summons->summons_number}.");
    }

    public function attendance(Request $request, BarangayCase $case, Summons $summons): RedirectResponse
    {
        $validated = $request->validate([
            'attendance' => ['required', 'string', Rule::in(['appeared', 'failed_to_appear', 'pending'])],
        ]);

        $summons->update(['attendance' => $validated['attendance']]);

        return to_route('admin.cases.show', $case)
            ->with('success', "Attendance recorded for summons {$summons->summons_number}.");
    }

    public function void(Request $request, BarangayCase $case, Summons $summons): RedirectResponse
    {
        $validated = $request->validate([
            'void_note' => ['required', 'string', 'min:5'],
        ]);

        $summons->update([
            'voided_at' => now(),
            'void_note' => $validated['void_note'],
        ]);

        return to_route('admin.cases.show', $case)
            ->with('success', "Summons {$summons->summons_number} voided.");
    }

    public function print(BarangayCase $case, Summons $summons): View
    {
        return view('admin.case-actions.summons.print', [
            'case' => $case,
            'summons' => $summons->load(['respondentCaseParty.party', 'issuedBy']),
        ]);
    }

    private function placeholder(
        string $title,
        string $message,
        BarangayCase $case,
        ?string $context = null
    ): View {
        return view('admin.case-actions.placeholder', [
            'title' => $title,
            'message' => $message,
            'context' => $context ?? "Case {$case->case_number}",
            'backUrl' => route('admin.cases.show', $case),
            'backLabel' => 'Back to case',
        ]);
    }
}
