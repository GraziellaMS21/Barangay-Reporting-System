<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purok;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = Report::query()->with('purok');

        // Filter: Status
        if ($request->filled('status') && array_key_exists($request->input('status'), Report::STATUSES)) {
            $query->where('status', $request->input('status'));
        }

        // Filter: Purok
        if ($request->filled('purok_id')) {
            $query->where('purok_id', $request->input('purok_id'));
        }

        // Filter: Search tracking code or description
        if ($request->filled('q')) {
            $term = addcslashes($request->input('q'), '%_\\');
            $query->where(function ($sub) use ($term) {
                $sub->where('tracking_code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Order: open reports first, then closed (resolved, out_of_scope, merged, referred), each newest first.
        // Note: Phase 5 replaces this ordering with priority score and emergency-first ordering.
        $closedStatusesList = "'" . implode("','", Report::CLOSED_STATUSES) . "'";
        $query->orderByRaw("CASE WHEN status IN ({$closedStatusesList}) THEN 1 ELSE 0 END ASC")
            ->latest('created_at')
            ->latest('id');

        $reports = $query->paginate(15)->withQueryString();

        // Stat counts
        $statusCounts = Report::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalReports = Report::count();
        $statTotals = [
            'total' => $totalReports,
            'new' => $statusCounts->get('submitted', 0),
            'under_review' => $statusCounts->get('under_review', 0),
            'in_progress' => $statusCounts->get('in_progress', 0),
            'resolved' => $statusCounts->get('resolved', 0),
        ];

        $puroks = Purok::orderBy('name')->get();

        return view('admin.reports.index', compact(
            'reports',
            'statusCounts',
            'statTotals',
            'puroks'
        ));
    }

    public function show(Report $report): View
    {
        $report->load(['purok', 'statusLogs.user']);
        $manualStatuses = Report::MANUAL_STATUSES;

        return view('admin.reports.show', compact('report', 'manualStatuses'));
    }

    public function updateStatus(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Report::MANUAL_STATUSES)],
            'note' => [
                'nullable',
                'string',
                'max:500',
                'required_if:status,out_of_scope',
            ],
        ], [
            'note.required_if' => 'A recorded explanation is required when a report is marked out of scope.',
        ]);

        // Reject a no-op (same status and empty note)
        if ($request->input('status') === $report->status && blank($request->input('note'))) {
            return back()
                ->withErrors(['status' => 'No changes made. Please select a different status or add a note.'])
                ->withInput();
        }

        DB::transaction(function () use ($report, $validated, $request) {
            $fromStatus = $report->status;
            $toStatus = $validated['status'];
            $note = $validated['note'] ?? null;

            $report->update(['status' => $toStatus]);

            $report->statusLogs()->create([
                'user_id' => $request->user()->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'note' => $note,
            ]);
        });

        return back()->with('success', 'Report status updated successfully.');
    }
}
