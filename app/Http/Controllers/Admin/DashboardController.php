<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Report;
use App\Services\HotspotService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, HotspotService $hotspotService): View
    {
        $reports = Report::query()->with(['category', 'purok'])->latest()->get();
        $counts = [
            'awaiting_review' => Report::query()->where('status', 'submitted')->count(),
            'high_or_critical' => Report::query()->whereIn('status', ['under_review', 'in_progress', 'routed'])->whereIn('urgency', ['High', 'Critical'])->count(),
            'in_progress' => Report::query()->where('status', 'in_progress')->count(),
            'resolved' => Report::query()->where('status', 'resolved')->count(),
        ];

        $categoryBreakdown = Category::query()->withCount('reports')->get()->map(function ($category) {
            return [
                'label' => $category->name,
                'total' => $category->reports_count,
            ];
        });

        $purokBreakdown = Report::query()->with('purok')->get()->groupBy(fn ($report) => $report->purok?->name ?? 'Unassigned');

        $recentReports = $reports->take(6);
        $hotspots = $hotspotService->buildCells($reports);

        return view('admin.dashboard', compact(
            'counts',
            'categoryBreakdown',
            'purokBreakdown',
            'recentReports',
            'hotspots',
            'reports'
        ));
    }
}
