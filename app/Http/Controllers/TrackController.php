<?php

namespace App\Http\Controllers;

use App\Events\ResolutionConfirmed;
use App\Models\Feedback;
use App\Models\Report;
use App\Models\ReportProofPhoto;
use App\Models\ResolutionProof;
use App\Models\StatusLog;
use App\Services\ProofPhotoService;
use App\Services\ReportStatusService;
use App\Support\ReportStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                'landmark',
                'description',
                'photo_path',
                'status',
                'no_action_reason',
                'reopened_count',
                'resolved_at',
                'resolution_confirmed_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'photos:id,report_id,photo_path',
                'purok:id,name',
                'category:id,name,sla_hours',
                'office:id,name',
                'statusLogs' => fn ($query) => $query->with(['user:id,name,role', 'office:id,name'])->oldest(),
                'infoRequests' => fn ($query) => $query->with('requester:id,name')->latest(),
                'resolutionProofs' => fn ($query) => $query->with('user:id,name')->latest(),
                'proofPhotos' => fn ($query) => $query->oldest(),
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

    public function respondToInfoRequest(
        Request $request,
        string $trackingCode,
        ReportStatusService $statusService
    ): RedirectResponse {
        $data = $request->validate([
            'response' => ['required', 'string', 'min:2', 'max:2000'],
        ], [
            'response.required' => 'Please enter your response to the staff question.',
            'response.min' => 'Response must be at least 2 characters.',
        ]);

        $report = Report::where('tracking_code', $trackingCode)->firstOrFail();
        $infoRequest = $report->infoRequests()->whereNull('responded_at')->latest('id')->first();

        if (! $infoRequest) {
            return back()->withErrors(['info_response' => 'No pending information request found for this report.']);
        }

        DB::transaction(function () use ($report, $infoRequest, $data, $statusService) {
            $infoRequest->update([
                'response' => $data['response'],
                'responded_at' => now(),
            ]);

            $hasOtherPending = $report->infoRequests()
                ->whereNull('responded_at')
                ->where('id', '!=', $infoRequest->id)
                ->exists();

            if (! $hasOtherPending && $report->status === ReportStatus::NEEDS_INFO) {
                $previousStatus = $report->statusLogs()
                    ->where('to_status', ReportStatus::NEEDS_INFO)
                    ->where('from_status', '!=', ReportStatus::NEEDS_INFO)
                    ->latest('id')
                    ->value('from_status');

                $targetStatus = ($previousStatus && $statusService->canTransition(ReportStatus::NEEDS_INFO, $previousStatus))
                    ? $previousStatus
                    : ReportStatus::UNDER_REVIEW;

                $statusService->transition(
                    $report,
                    $targetStatus,
                    'Resident provided additional info: '.Str::limit($data['response'], 120),
                    null,
                    ['visible_to_resident' => true]
                );
            } else {
                StatusLog::create([
                    'report_id' => $report->id,
                    'from_status' => $report->status,
                    'to_status' => $report->status,
                    'note' => 'Resident provided additional info: '.Str::limit($data['response'], 120),
                ]);
            }
        });

        return redirect()->route('track.show', $report->tracking_code)
            ->with('status', 'Your response has been sent to barangay staff.');
    }

    public function confirmResolution(
        Request $request,
        string $trackingCode,
        ReportStatusService $statusService
    ): RedirectResponse {
        $data = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $report = Report::where('tracking_code', $trackingCode)->firstOrFail();

        if ($report->status !== ReportStatus::AWAITING_CONFIRMATION) {
            return redirect()->route('track.show', $report->tracking_code)
                ->withErrors(['status' => 'This report is not awaiting resolution confirmation.']);
        }

        $feedback = null;
        DB::transaction(function () use ($report, $data, $statusService, &$feedback) {
            if (! empty($data['rating'])) {
                $feedback = Feedback::updateOrCreate(
                    ['report_id' => $report->id],
                    [
                        'rating' => (int) $data['rating'],
                        'comment' => $data['comment'] ?? null,
                    ]
                );
            }

            $statusService->transition(
                $report,
                ReportStatus::RESOLVED,
                'Resident confirmed the problem has been resolved.',
                null,
                ['visible_to_resident' => true]
            );

            $report->update([
                'resolution_confirmed_at' => now(),
            ]);
        });

        // Contract: Dispatches ResolutionConfirmed
        ResolutionConfirmed::dispatch($report->fresh(), true, $feedback);

        return redirect()->route('track.show', $report->tracking_code)
            ->with('status', 'Thank you! You have confirmed this report is resolved.');
    }

    public function reopen(
        Request $request,
        string $trackingCode,
        ReportStatusService $statusService
    ): RedirectResponse {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'reason.required' => 'Please explain why the issue is not yet resolved.',
            'reason.min' => 'Explanation must be at least 10 characters.',
        ]);

        $report = Report::where('tracking_code', $trackingCode)->firstOrFail();

        if (! in_array($report->status, [ReportStatus::AWAITING_CONFIRMATION, ReportStatus::RESOLVED], true)) {
            return redirect()->route('track.show', $report->tracking_code)
                ->withErrors(['status' => 'This report cannot be reopened from its current status.']);
        }

        DB::transaction(function () use ($report, $data, $statusService) {
            $statusService->transition(
                $report,
                ReportStatus::REOPENED,
                'Resident reopened report: '.$data['reason'],
                null,
                ['visible_to_resident' => true]
            );

            $report->update([
                'resolution_confirmed_at' => null,
            ]);
        });

        // Contract: Dispatches ResolutionConfirmed
        ResolutionConfirmed::dispatch($report->fresh(), false, null);

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

    public function proofPhoto(
        string $trackingCode,
        ReportProofPhoto $proofPhoto,
        ProofPhotoService $proofPhotoService
    ): StreamedResponse {
        $report = Report::where('tracking_code', $trackingCode)->firstOrFail();
        abort_unless($proofPhoto->report_id === $report->id, 404);

        return $proofPhotoService->response($proofPhoto);
    }

    public function legacyProofPhoto(
        string $trackingCode,
        ResolutionProof $proof
    ): StreamedResponse {
        $report = Report::where('tracking_code', $trackingCode)->firstOrFail();
        abort_unless($proof->report_id === $report->id && $proof->photo_path, 404);

        if (Storage::disk('public')->exists($proof->photo_path)) {
            return Storage::disk('public')->response($proof->photo_path);
        }

        abort_unless(Storage::disk('local')->exists($proof->photo_path), 404);

        return Storage::disk('local')->response($proof->photo_path);
    }
}
