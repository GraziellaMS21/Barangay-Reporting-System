<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;

class HotspotService
{
    public function buildCells(Collection $reports): array
    {
        $cells = [];

        foreach ($reports as $report) {
            if ($report->lat === null || $report->lng === null) {
                continue;
            }

            $latKey = round((float) $report->lat, 3);
            $lngKey = round((float) $report->lng, 3);
            $key = sprintf('%s,%s', $latKey, $lngKey);

            $cells[$key] = ($cells[$key] ?? 0) + 1;
        }

        return $cells;
    }

    public function weeklyTrend(): array
    {
        return Report::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'day' => $row->day,
                'total' => (int) $row->total,
            ])
            ->all();
    }
}
