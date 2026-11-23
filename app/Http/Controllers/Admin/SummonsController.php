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

    public function service(Request $request, BarangayCase $case, Summons $summons): View
    {
        return $this->placeholder(
            'Summons service record',
            'Recording summons service details is not available yet.',
            $case
        );
    }

    public function attendance(Request $request, BarangayCase $case, Summons $summons): View
    {
        return $this->placeholder(
            'Summons attendance',
            'Recording summons attendance is not available yet.',
            $case
        );
    }

    public function void(Request $request, BarangayCase $case, Summons $summons): View
    {
        return $this->placeholder(
            'Void summons',
            'Voiding a summons and recording the required reason are not available yet.',
            $case
        );
    }

    public function print(BarangayCase $case, Summons $summons): View
    {
        return $this->placeholder(
            'Print summons',
            'A printable official summons is not available yet.',
            $case,
            "Summons {$summons->summons_number}"
        );
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
