<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    public function index()
    {
        return view('resident.track');
    }

    public function search(Request $request)
    {
        $request->validate(['tracking_code' => 'required|string|max:30']);
        return redirect()->route('track.show', strtoupper(trim($request->tracking_code)));
    }

    public function show(string $trackingCode)
    {
        $report = Report::with(['purok', 'statusLogs'])
            ->where('tracking_code', $trackingCode)->first();

        if (!$report) {
            return redirect()->route('track.index')
                ->withErrors(['tracking_code' => 'No report found with that code.']);
        }

        return view('resident.show', compact('report'));
    }
}