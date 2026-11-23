<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\Hearing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HearingController extends Controller
{
    public function index(): View
    {
        return view('admin.case-actions.placeholder', [
            'title' => 'Conciliation hearings',
            'message' => 'The hearings calendar is not available yet.',
        ]);
    }

    public function store(Request $request, BarangayCase $case): View
    {
        return $this->placeholder('Schedule a hearing', 'Scheduling a hearing is not available yet.', $case);
    }

    public function update(Request $request, BarangayCase $case, Hearing $hearing): View
    {
        return $this->placeholder('Update a hearing', 'Updating hearing details is not available yet.', $case);
    }

    public function outcome(Request $request, BarangayCase $case, Hearing $hearing): View
    {
        return $this->placeholder('Record a hearing outcome', 'Recording hearing attendance or outcome is not available yet.', $case);
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
