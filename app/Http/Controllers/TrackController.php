<?php

namespace App\Http\Controllers;

use App\Events\ResolutionConfirmed;
use App\Models\Feedback;
use App\Models\Report;
use App\Models\StatusLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TrackController extends Controller
{
    public function index(): View
    {
        return view('resident.track');
    }

    public function search(Request $request): RedirectResponse
    {
        $request->validate(['tracking_code' => 'required|string|max:30']);

        return redirect()->route('track.show', strtoupper(trim($request->tracking_code)));
    }

    public function show(string $trackingCode): View|RedirectResponse
    {
        $report = Report::query()
            ->select([
                'id',
                'tracking_code',
                'report_type',
                'category_id',
                'office_id',
                'purok_id',
                'status',
                'no_action_reason',
                'reopened_count',
                'resolved_at',
                'resolution_confirmed_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'purok:id,name',
                'category:id,name',
                'office:id,name',
                'statusLogs' => fn ($query) => $query->with(['user:id,name,role', 'office:id,name'])->oldest(),
                'infoRequests' => fn ($query) => $query->with('requester:id,name')->latest(),
                'resolutionProofs' => fn ($query) => $query->with('user:id,name')->latest(),
                'feedback',
                // Referral tracking: only the plain-language data the resident may see.
                'activeReferral.office:id,name',
                'activeReferral.updates' => fn ($query) => $query
                    ->where('visible_to_resident', true)
                    ->whereNotNull('occurred_at')
                    ->orderByDesc('occurred_at'),
            ])
            ->where('tracking_code', $trackingCode)
            ->first();

        if (! $report) {
            return redirect()->route('track.index')
                ->withErrors(['tracking_code' => 'No report found with that code.']);
        }

        $pendingInfoRequest = $report->infoRequests->first(fn ($req) => $req->responded_at === null);
        $latestResolutionProof = $report->resolutionProofs->first();

        return view('resident.show', compact('report', 'pendingInfoRequest', 'latestResolutionProof'));
    }

    public function respondToInfoRequest(Request $request, string $trackingCode): RedirectResponse
    {
        $data = $request->validate([
            'response' => ['required', 'string', 'min:2', 'max:2000'],
        ], [
            'response.required' => 'Please enter your response to the staff question.',
            'response.min' => 'Response must be at least 2 characters.',
        ]);

        $report = Report::where('tracking_code', $trackingCode)->firstOrFail();
        $infoRequest = $report->infoRequests()->whereNull('responded_at')->latest()->first();

        if (! $infoRequest) {
            return back()->withErrors(['info_response' => 'No pending information request found for this report.']);
        }

        $infoRequest->update([
            'response' => $data['response'],
            'responded_at' => now(),
        ]);

        StatusLog::create([
            'report_id' => $report->id,
            'from_status' => $report->status,
            'to_status' => $report->status,
            'note' => 'Resident provided additional info: '.Str::limit($data['response'], 120),
        ]);

        return redirect()->route('track.show', $report->tracking_code)
            ->with('status', 'Your response has been sent to barangay staff.');
    }

    public function confirmResolution(Request $request, string $trackingCode): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $report = Report::where('tracking_code', $trackingCode)->firstOrFail();

        $feedback = null;
        if (! empty($data['rating'])) {
            $feedback = Feedback::updateOrCreate(
                ['report_id' => $report->id],
                [
                    'rating' => (int) $data['rating'],
                    'comment' => $data['comment'] ?? null,
                ]
            );
        }

        $previousStatus = $report->status;
        $report->update([
            'status' => 'resolved',
            'resolved_at' => $report->resolved_at ?? now(),
            'resolution_confirmed_at' => now(),
        ]);

        StatusLog::create([
            'report_id' => $report->id,
            'from_status' => $previousStatus,
            'to_status' => 'resolved',
            'note' => 'Resident confirmed the problem has been resolved.',
        ]);

        // Contract: Dispatches ResolutionConfirmed
        ResolutionConfirmed::dispatch($report, true, $feedback);

        return redirect()->route('track.show', $report->tracking_code)
            ->with('status', 'Thank you! You have confirmed this report is resolved.');
    }

    public function reopen(Request $request, string $trackingCode): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'reason.required' => 'Please explain why the issue is not yet resolved.',
            'reason.min' => 'Explanation must be at least 10 characters.',
        ]);

        $report = Report::where('tracking_code', $trackingCode)->firstOrFail();
        $previousStatus = $report->status;

        $report->increment('reopened_count');
        $report->update([
            'status' => 'reopened',
            'resolution_confirmed_at' => null,
        ]);

        StatusLog::create([
            'report_id' => $report->id,
            'from_status' => $previousStatus,
            'to_status' => 'reopened',
            'note' => 'Resident reopened report: '.$data['reason'],
        ]);

        // Contract: Dispatches ResolutionConfirmed
        ResolutionConfirmed::dispatch($report, false, null);

        return redirect()->route('track.show', $report->tracking_code)
            ->with('status', 'Report has been reopened. Barangay staff will follow up on your concern.');
    }

    public function feedback(Request $request, string $trackingCode): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Please select a rating from 1 to 5 stars.',
        ]);

        $report = Report::where('tracking_code', $trackingCode)->firstOrFail();

        Feedback::updateOrCreate(
            ['report_id' => $report->id],
            [
                'rating' => (int) $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        return redirect()->route('track.show', $report->tracking_code)
            ->with('status', 'Thank you for your feedback! Your rating helps improve barangay services.');
    }
}
