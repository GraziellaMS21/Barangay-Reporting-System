<?php

// Engine routes
use App\Http\Controllers\ReportController;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/api/nearby-reports', function (Request $request) {
    $lat = $request->query('lat');
    $lng = $request->query('lng');
    $categoryId = $request->query('category') ?: $request->query('category_id');

    if (! is_numeric($lat) || ! is_numeric($lng)) {
        return response()->json([]);
    }

    $lat = (float) $lat;
    $lng = (float) $lng;

    $reports = Report::query()
        ->whereNotIn('status', Report::CLOSED_STATUSES)
        ->where('is_emergency', false)
        ->whereNotNull('lat')
        ->whereNotNull('lng')
        ->when($categoryId, function ($q) use ($categoryId) {
            if (is_numeric($categoryId)) {
                $q->where('category_id', $categoryId);
            } else {
                $q->whereHas('category', fn ($cat) => $cat->where('slug', $categoryId));
            }
        })
        ->with('category:id,name')
        ->latest()
        ->limit(10)
        ->get();

    $nearby = $reports->filter(function ($report) use ($lat, $lng) {
        $earthRadius = 6371000;
        $dLat = deg2rad($report->lat - $lat);
        $dLng = deg2rad($report->lng - $lng);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat)) * cos(deg2rad($report->lat)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        $report->distance_m = round($distance);

        return $distance <= 300;
    })->map(function ($report) {
        return [
            'tracking_code' => $report->tracking_code,
            'category' => $report->category?->name ?? 'Issue',
            'status' => Report::STATUSES[$report->status] ?? ucfirst($report->status),
            'distance_m' => $report->distance_m,
            'created_at' => $report->created_at->diffForHumans(),
        ];
    })->values();

    return response()->json($nearby);
});

Route::post('/api/detect-location', [ReportController::class, 'detectLocation'])
    ->middleware('throttle:30,1')
    ->name('api.detect-location');
