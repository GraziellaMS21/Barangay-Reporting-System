<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseEscalationController extends Controller
{
    public function create(BarangayCase $case): View
    {
        return $this->placeholder(
            'Escalate case',
            'The escalation form is not available yet. Official CFA and endorsement documents cannot be generated.',
            $case
        );
    }

    public function store(Request $request, BarangayCase $case): View
    {
        return $this->placeholder(
            'Escalation not submitted',
            'The escalation workflow is not available yet. No case status changed and no referral was sent.',
            $case
        );
    }

    public function letter(BarangayCase $case): View
    {
        return $this->placeholder(
            'Print endorsement letter',
            'Official CFA and endorsement letter generation is not available yet.',
            $case
        );
    }

    private function placeholder(string $title, string $message, BarangayCase $case): View
    {
        return view('admin.case-actions.placeholder', [
            'title' => $title,
            'message' => $message,
            'context' => "Case {$case->case_number}",
            'backUrl' => route('admin.cases.show', $case),
            'backLabel' => 'Back to case',
        ]);
    }
}
