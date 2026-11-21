<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use Illuminate\Http\Request;

class CaseEscalationController extends Controller
{
    public function create(BarangayCase $case)
    {
        return view('stub', ['title' => "Escalate Case {$case->case_number}", 'owner' => 'WS4']);
    }

    public function store(Request $request, BarangayCase $case)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Case escalated.');
    }

    public function letter(BarangayCase $case)
    {
        return view('stub', ['title' => "Endorsement Letter for Case {$case->case_number}", 'owner' => 'WS4']);
    }
}
