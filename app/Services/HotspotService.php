<?php

namespace App\Services;

use App\Models\Report;
use Carbon\CarbonImmutable;
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
            $key = sprintf('%.3f,%.3f', $latKey, $lngKey);

            $cells[$key] = ($cells[$key] ?? 0) + 1;
        }

        return $cells;
    }

    public function weeklyTrend(): array
    {
        $now = CarbonImmutable::now();
        $firstWeek = $now->startOfWeek()->subWeeks(7);
        $weekStarts = collect(range(0, 7))
            ->map(fn (int $offset): CarbonImmutable => $firstWeek->addWeeks($offset));

        return $weekStarts
            ->map(function (CarbonImmutable $weekStart, int $index) use ($weekStarts, $now): array {
                $query = Report::query()->where('created_at', '>=', $weekStart);
                $nextWeekStart = $weekStarts->get($index + 1);

                if ($nextWeekStart instanceof CarbonImmutable) {
                    $query->where('created_at', '<', $nextWeekStart);
                } else {
                    $query->where('created_at', '<=', $now);
                }

                return [
                    'week' => $weekStart->format('M j'),
                    'total' => $query->count(),
                ];
            })
            ->all();
    }
}
