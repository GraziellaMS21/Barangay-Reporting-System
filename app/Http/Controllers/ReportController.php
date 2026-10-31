<?php

namespace App\Http\Controllers;

use App\Models\EmergencyContact;
use App\Models\Purok;
use App\Models\Report;
use App\Models\StatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function create()
    {
        return view('resident.create', [
            'puroks'   => Purok::orderBy('name')->get(),
            'contacts' => EmergencyContact::all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description'    => 'required|string|min:10|max:2000',
            'photo'          => 'nullable|image|max:5120',
            'lat'            => 'nullable|numeric|between:-90,90',
            'lng'            => 'nullable|numeric|between:-180,180',
            'purok_id'       => 'nullable|exists:puroks,id',
            'landmark'       => 'nullable|string|max:255',
            'reporter_email' => 'nullable|email|max:255',
        ]);

        if (!$request->filled('lat') && !$request->filled('purok_id') && !$request->filled('landmark')) {
            return back()->withInput()
                ->withErrors(['location' => 'Please set a pin on the map, choose a purok, or enter a landmark.']);
        }

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('reports', 'public')
            : null;

        $report = DB::transaction(function () use ($data, $photoPath) {
            $report = Report::create([
                'tracking_code'  => Report::generateTrackingCode(),
                'description'    => $data['description'],
                'photo_path'     => $photoPath,
                'lat'            => $data['lat'] ?? null,
                'lng'            => $data['lng'] ?? null,
                'purok_id'       => $data['purok_id'] ?? null,
                'landmark'       => $data['landmark'] ?? null,
                'reporter_email' => $data['reporter_email'] ?? null,
                'status'         => 'submitted',
            ]);

            StatusLog::create([
                'report_id'   => $report->id,
                'from_status' => null,
                'to_status'   => 'submitted',
                'note'        => 'Report received.',
            ]);

            return $report;
        });

        // PHASE 3: call GeminiService here (inside try/catch) after the report is saved.

        return redirect()->route('reports.success', $report->tracking_code);
    }

    public function success(string $trackingCode)
    {
        $report = Report::where('tracking_code', $trackingCode)->firstOrFail();
        return view('resident.success', compact('report'));
    }
}