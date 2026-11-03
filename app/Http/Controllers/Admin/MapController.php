<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Purok;
use App\Models\Report;
use App\Services\HotspotService;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(HotspotService $hotspotService): View
    {
        $reports = Report::query()
            ->select([
                'id',
                'tracking_code',
                'category_id',
                'purok_id',
                'status',
                'urgency',
                'lat',
                'lng',
                'created_at',
            ])
            ->with(['purok:id,name', 'category:id,name'])
            ->latest()
            ->get();
        $puroks = Purok::query()->orderBy('name')->get(['id', 'name']);
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $hotspots = collect($hotspotService->buildCells($reports))
            ->sortDesc()
            ->take(8);
        $mapReports = $reports
            ->filter(fn (Report $report): bool => $report->lat !== null && $report->lng !== null)
            ->map(fn (Report $report): array => [
                'trackingCode' => $report->tracking_code,
                'url' => route('admin.reports.show', $report),
                'categoryId' => $report->category_id === null ? '__unclassified__' : (string) $report->category_id,
                'category' => $report->category?->name ?? 'Unclassified',
                'purokId' => $report->purok_id === null ? '__unassigned__' : (string) $report->purok_id,
                'purok' => $report->purok?->name ?? 'Unassigned',
                'status' => $report->status,
                'statusLabel' => Report::STATUSES[$report->status] ?? $report->status,
                'urgency' => $report->urgency ?? 'Unspecified',
                'lat' => $report->lat,
                'lng' => $report->lng,
                'createdAt' => $report->created_at->toDateString(),
                'cellKey' => sprintf('%.3f,%.3f', round($report->lat, 3), round($report->lng, 3)),
            ])
            ->values();
        $weeklyTrend = $hotspotService->weeklyTrend();

        return view('admin.map', compact('categories', 'hotspots', 'mapReports', 'puroks', 'weeklyTrend'));
    }
}
