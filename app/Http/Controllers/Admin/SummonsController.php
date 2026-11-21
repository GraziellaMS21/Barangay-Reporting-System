<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\Summons;
use Illuminate\Http\Request;

class SummonsController extends Controller
{
    public function create(BarangayCase $case)
    {
        return view('stub', ['title' => "Issue Summons for Case {$case->case_number}", 'owner' => 'WS4']);
    }

    public function store(Request $request, BarangayCase $case)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Summons issued.');
    }

    public function service(Request $request, BarangayCase $case, Summons $summons)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Summons service recorded.');
    }

    public function attendance(Request $request, BarangayCase $case, Summons $summons)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Summons attendance recorded.');
    }

    public function void(Request $request, BarangayCase $case, Summons $summons)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Summons voided.');
    }

    public function print(BarangayCase $case, Summons $summons)
    {
        return view('stub', ['title' => "Print Summons: {$summons->summons_number}", 'owner' => 'WS4']);
    }
}
