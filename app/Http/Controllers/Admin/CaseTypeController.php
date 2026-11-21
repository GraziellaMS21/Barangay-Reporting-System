<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseType;
use Illuminate\Http\Request;

class CaseTypeController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Case Types & Classifications', 'owner' => 'WS3']);
    }

    public function create()
    {
        return view('stub', ['title' => 'Create Case Type', 'owner' => 'WS3']);
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.case-types.index')->with('success', 'Case type created.');
    }

    public function show(CaseType $case_type)
    {
        return view('stub', ['title' => "Case Type: {$case_type->name}", 'owner' => 'WS3']);
    }

    public function edit(CaseType $case_type)
    {
        return view('stub', ['title' => "Edit Case Type: {$case_type->name}", 'owner' => 'WS3']);
    }

    public function update(Request $request, CaseType $case_type)
    {
        return redirect()->route('admin.case-types.index')->with('success', 'Case type updated.');
    }

    public function destroy(CaseType $case_type)
    {
        return redirect()->route('admin.case-types.index')->with('success', 'Case type deleted.');
    }
}
