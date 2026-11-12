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
        $report = Report::query()
            ->select(['id', 'tracking_code', 'status', 'purok_id', 'created_at'])
            ->with([
                'purok:id,name',
                'statusLogs:id,report_id,to_status,created_at,note',
            ])
            ->where('tracking_code', $trackingCode)
            ->first();

        if (! $report) {
            return redirect()->route('track.index')
                ->withErrors(['tracking_code' => 'No report found with that code.']);
        }

        return view('resident.show', compact('report'));
    }
}
