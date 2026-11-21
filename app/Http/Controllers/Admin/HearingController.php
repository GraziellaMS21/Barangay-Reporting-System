<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\Hearing;
use Illuminate\Http\Request;

class HearingController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Scheduled Conciliation Hearings', 'owner' => 'WS4']);
    }

    public function store(Request $request, BarangayCase $case)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Hearing scheduled.');
    }

    public function update(Request $request, BarangayCase $case, Hearing $hearing)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Hearing updated.');
    }

    public function outcome(Request $request, BarangayCase $case, Hearing $hearing)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Hearing outcome recorded.');
    }
}
