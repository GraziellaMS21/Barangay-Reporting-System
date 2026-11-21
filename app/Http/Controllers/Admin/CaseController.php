<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\Report;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Barangay Cases List', 'owner' => 'WS3']);
    }

    public function create()
    {
        return view('stub', ['title' => 'File New Barangay Case', 'owner' => 'WS3']);
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.cases.index')->with('success', 'Case filed successfully.');
    }

    public function show(BarangayCase $case)
    {
        return view('admin.cases.show', compact('case'));
    }

    public function edit(BarangayCase $case)
    {
        return view('stub', ['title' => "Edit Case: {$case->case_number}", 'owner' => 'WS3']);
    }

    public function update(Request $request, BarangayCase $case)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Case updated.');
    }

    public function status(Request $request, BarangayCase $case)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Case status updated.');
    }

    public function assign(Request $request, BarangayCase $case)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Case assigned.');
    }

    public function storeNote(Request $request, BarangayCase $case)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Note added.');
    }

    public function convertFromReport(Request $request, Report $report)
    {
        return redirect()->route('admin.cases.create')->with('info', "Converting report {$report->tracking_code} to case.");
    }
}
