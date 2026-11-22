<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\DuplicateSuggestion;
use App\Models\Referral;
use App\Models\Report;
use App\Services\DashboardAnalyticsService;
use App\Services\DashboardInsightsService;
use App\Services\OverdueService;
use App\Support\CaseStatus;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        DashboardAnalyticsService $dashboardAnalyticsService,
        DashboardInsightsService $dashboardInsightsService
    ): View {
        $counts = [
            'needs_review' => Report::query()->whereIn('status', ['submitted', 'under_review'])->count(),
            'awaiting_review' => Report::query()->whereIn('status', ['submitted', 'under_review'])->count(),
            'high_or_critical' => Report::query()->whereIn('status', ['under_review', 'in_progress', 'routed'])->whereIn('urgency', ['High', 'Critical'])->count(),
            'in_progress' => Report::query()->where('status', 'in_progress')->count(),
            'overdue' => Report::query()->whereNotNull('due_at')->where('due_at', '<', now())->whereNotIn('status', Report::CLOSED_STATUSES)->count(),
            'awaiting_resident' => Report::query()->whereIn('status', ['needs_info', 'awaiting_confirmation'])->count(),
            'resolved' => Report::query()->where('status', 'resolved')->count(),
            'emergency' => Report::query()
                ->where(fn ($query) => $query
                    ->where('is_emergency', true)
                    ->orWhere('status', 'emergency_flagged'))
                ->count(),
        ];

        $chartData = $dashboardAnalyticsService->chartData();
        $recentReports = Report::query()->with('category')->latest()->limit(5)->get();
        $resolvedReports = Report::query()
            ->where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at']);
        $averageResolutionHours = $resolvedReports->isEmpty()
            ? null
            : round($resolvedReports->avg(
                fn (Report $report): float => abs($report->created_at->diffInMinutes($report->resolved_at)) / 60
            ), 1);
        $topPriorityReports = Report::query()
            ->whereNotIn('status', [...Report::CLOSED_STATUSES, 'emergency_flagged'])
            ->orderByDesc('priority_score')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'tracking_code', 'priority_score', 'urgency', 'status']);
        $needsAttentionReports = Report::query()
            ->with(['category:id,name', 'purok:id,name'])
            ->whereNotIn('status', Report::CLOSED_STATUSES)
            ->orderByDesc('is_emergency')
            ->orderByRaw('CASE WHEN due_at IS NOT NULL AND due_at < ? THEN 0 ELSE 1 END', [now()->toDateTimeString()])
            ->orderByDesc('priority_score')
            ->orderBy('created_at')
            ->limit(5)
            ->get(['id', 'tracking_code', 'description', 'category_id', 'purok_id', 'priority_score', 'urgency', 'status', 'reopened_count', 'due_at', 'created_at', 'is_emergency']);
        $pendingDuplicateCount = DuplicateSuggestion::query()->where('decision', 'pending')->count();
        $pendingReferralCount = Referral::query()->needsAttention()->count();
        $pendingDuplicateSuggestions = DuplicateSuggestion::query()
            ->where('decision', 'pending')
            ->with(['report:id,tracking_code', 'candidate:id,tracking_code'])
            ->latest()
            ->limit(5)
            ->get();
        $pendingReferrals = Referral::query()
            ->needsAttention()
            ->with(['report:id,tracking_code', 'office:id,name'])
            ->orderByRaw('CASE WHEN follow_up_at IS NOT NULL AND follow_up_at < ? THEN 0 ELSE 1 END', [now()->toDateTimeString()])
            ->latest('id')
            ->limit(5)
            ->get();
        $dashboardInsights = $dashboardInsightsService->generate([
            'reports_awaiting_review' => $counts['awaiting_review'],
            'reports_high_or_critical' => $counts['high_or_critical'],
            'reports_in_progress' => $counts['in_progress'],
            'reports_resolved' => $counts['resolved'],
            'reports_emergency_flagged' => $counts['emergency'],
            'reports_by_category' => array_combine(
                $chartData['category']['labels'],
                $chartData['category']['counts']
            ) ?: [],
            'reports_by_purok' => array_combine(
                $chartData['purok']['labels'],
                $chartData['purok']['counts']
            ) ?: [],
        ]);

        $casesEnabled = (bool) setting('cases.enabled', true);
        $caseMetrics = null;

        if ($casesEnabled) {
            $openCasesCount = BarangayCase::whereIn('status', [CaseStatus::FILED, CaseStatus::IN_MEDIATION, CaseStatus::IN_CONCILIATION])->count();
            $filedCount = BarangayCase::where('status', CaseStatus::FILED)->count();
            $settledCount = BarangayCase::where('status', CaseStatus::SETTLED)->count();
            $escalatedCount = BarangayCase::where('status', CaseStatus::ESCALATED)->count();

            $settledCases = BarangayCase::where('status', CaseStatus::SETTLED)
                ->whereNotNull('filed_on')
                ->whereNotNull('settled_on')
                ->get(['filed_on', 'settled_on']);

            $avgDaysToSettle = $settledCases->isEmpty()
                ? 0
                : round($settledCases->avg(fn ($c) => abs(Carbon::parse($c->filed_on)->diffInDays(Carbon::parse($c->settled_on)))), 1);

            $overdueCount = 0;
            $overdueService = app(OverdueService::class);
            foreach (BarangayCase::whereIn('status', [CaseStatus::IN_MEDIATION, CaseStatus::IN_CONCILIATION])->get() as $c) {
                if ($overdueService->forCase($c)['is_overdue']) {
                    $overdueCount++;
                }
            }

            $caseMetrics = [
                'open_cases' => $openCasesCount,
                'cases_filed' => $filedCount,
                'cases_settled' => $settledCount,
                'ready_for_next' => $escalatedCount,
                'avg_days_to_settle' => $avgDaysToSettle,
                'overdue_cases' => $overdueCount,
            ];
        }

        return view('admin.dashboard', compact(
            'counts',
            'chartData',
            'needsAttentionReports',
            'averageResolutionHours',
            'pendingDuplicateCount',
            'pendingReferralCount',
            'pendingReferrals',
            'dashboardInsights',
            'casesEnabled',
            'caseMetrics'
        ));
    }
}
