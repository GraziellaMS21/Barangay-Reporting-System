<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseSettlementController extends Controller
{
    public function store(Request $request, BarangayCase $case): View
    {
        return view('admin.case-actions.placeholder', [
            'title' => 'Record a settlement',
            'message' => 'Recording settlement terms or attaching an agreement is not available yet.',
            'context' => "Case {$case->case_number}",
            'backUrl' => route('admin.cases.show', $case),
            'backLabel' => 'Back to case',
        ]);
    }
}
