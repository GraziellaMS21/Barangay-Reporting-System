<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DuplicateSuggestion;
use App\Models\Referral;
use App\Models\Report;
use App\Services\DashboardInsightsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardInsightsService $dashboardInsightsService): View
    {
        $counts = [
            'awaiting_review' => Report::query()->where('status', 'submitted')->count(),
            'high_or_critical' => Report::query()->whereIn('status', ['under_review', 'in_progress', 'routed'])->whereIn('urgency', ['High', 'Critical'])->count(),
            'in_progress' => Report::query()->where('status', 'in_progress')->count(),
            'resolved' => Report::query()->where('status', 'resolved')->count(),
            'emergency' => Report::query()->where('status', 'emergency_flagged')->count(),
        ];

        $categoryBreakdown = Category::query()->orderBy('name')->withCount('reports')->get()->map(function ($category) {
            return [
                'label' => $category->name,
                'total' => $category->reports_count,
            ];
        });

        $purokBreakdown = Report::query()
            ->leftJoin('puroks', 'reports.purok_id', '=', 'puroks.id')
            ->selectRaw("COALESCE(puroks.name, 'Unassigned') as label, COUNT(reports.id) as total")
            ->groupBy('puroks.name')
            ->orderBy('label')
            ->pluck('total', 'label');
        $recentReports = Report::query()->with('category')->latest()->limit(5)->get();
        $resolvedReports = Report::query()
            ->where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at']);
        $averageResolutionHours = $resolvedReports->isEmpty()
            ? null
            : round($resolvedReports->avg(
                fn (Report $report): float => $report->created_at->diffInMinutes($report->resolved_at) / 60
            ), 1);
        $topPriorityReports = Report::query()
            ->whereNotIn('status', [...Report::CLOSED_STATUSES, 'emergency_flagged'])
            ->orderByDesc('priority_score')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'tracking_code', 'priority_score', 'urgency', 'status']);
        $unreviewedReports = Report::query()
            ->where('status', 'submitted')
            ->latest()
            ->limit(5)
            ->get(['id', 'tracking_code', 'created_at']);
        $pendingDuplicateSuggestions = DuplicateSuggestion::query()
            ->where('decision', 'pending')
            ->with(['report:id,tracking_code', 'candidate:id,tracking_code'])
            ->latest()
            ->limit(5)
            ->get();
        $pendingReferrals = Referral::query()
            ->where('status', 'pending')
            ->with(['report:id,tracking_code', 'office:id,name'])
            ->latest()
            ->limit(5)
            ->get();
        $dashboardInsights = $dashboardInsightsService->generate([
            'reports_awaiting_review' => $counts['awaiting_review'],
            'reports_high_or_critical' => $counts['high_or_critical'],
            'reports_in_progress' => $counts['in_progress'],
            'reports_resolved' => $counts['resolved'],
            'reports_emergency_flagged' => $counts['emergency'],
            'reports_by_category' => $categoryBreakdown->pluck('total', 'label')->all(),
            'reports_by_purok' => $purokBreakdown->all(),
        ]);

        return view('admin.dashboard', compact(
            'counts',
            'categoryBreakdown',
            'purokBreakdown',
            'recentReports',
            'averageResolutionHours',
            'topPriorityReports',
            'unreviewedReports',
            'pendingDuplicateSuggestions',
            'pendingReferrals',
            'dashboardInsights'
        ));
    }
}
