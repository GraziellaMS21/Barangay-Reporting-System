<?php

namespace App\Services;

use App\Models\Report;
use Carbon\CarbonImmutable;

class DashboardAnalyticsService
{
    /**
     * @return array<string, array{labels: list<string>, counts: list<int>}>
     */
    public function chartData(): array
    {
        $categoryRows = Report::query()
            ->leftJoin('categories', 'reports.category_id', '=', 'categories.id')
            ->where('reports.status', '!=', 'emergency_flagged')
            ->selectRaw("COALESCE(categories.name, 'Unclassified') as label, COUNT(reports.id) as total")
            ->groupBy('categories.name')
            ->orderBy('label')
            ->get();

        $statusCounts = Report::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $purokRows = Report::query()
            ->leftJoin('puroks', 'reports.purok_id', '=', 'puroks.id')
            ->selectRaw("COALESCE(puroks.name, 'Unassigned') as label, COUNT(reports.id) as total")
            ->groupBy('puroks.name')
            ->orderByDesc('total')
            ->orderBy('label')
            ->limit(8)
            ->get();
        $urgencyCounts = Report::query()
            ->where('status', '!=', 'emergency_flagged')
            ->whereIn('urgency', ['Low', 'Medium', 'High', 'Critical'])
            ->select('urgency')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('urgency')
            ->pluck('total', 'urgency');
        $weeklyReports = $this->weeklyReports();

        return [
            'category' => [
                'labels' => $categoryRows->pluck('label')->all(),
                'counts' => $categoryRows->map(fn (object $row): int => (int) $row->total)->all(),
            ],
            'status' => [
                'labels' => array_values(Report::STATUSES),
                'counts' => array_map(
                    fn (string $status): int => (int) $statusCounts->get($status, 0),
                    array_keys(Report::STATUSES)
                ),
            ],
            'weekly' => [
                'labels' => $weeklyReports['labels'],
                'counts' => $weeklyReports['counts'],
            ],
            'purok' => [
                'labels' => $purokRows->pluck('label')->all(),
                'counts' => $purokRows->map(fn (object $row): int => (int) $row->total)->all(),
            ],
            'urgency' => [
                'labels' => ['Low', 'Medium', 'High', 'Critical'],
                'counts' => array_map(
                    fn (string $urgency): int => (int) $urgencyCounts->get($urgency, 0),
                    ['Low', 'Medium', 'High', 'Critical']
                ),
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, counts: list<int>}
     */
    private function weeklyReports(): array
    {
        $now = CarbonImmutable::now();
        $firstWeek = $now->startOfWeek()->subWeeks(7);
        $weekStarts = collect(range(0, 7))
            ->map(fn (int $offset): CarbonImmutable => $firstWeek->addWeeks($offset));

        return [
            'labels' => $weekStarts->map(fn (CarbonImmutable $week): string => $week->format('M d'))->all(),
            'counts' => $weekStarts
                ->map(function (CarbonImmutable $weekStart, int $index) use ($weekStarts, $now): int {
                    $query = Report::query()->where('created_at', '>=', $weekStart);
                    $nextWeekStart = $weekStarts->get($index + 1);

                    if ($nextWeekStart instanceof CarbonImmutable) {
                        $query->where('created_at', '<', $nextWeekStart);
                    } else {
                        $query->where('created_at', '<=', $now);
                    }

                    return $query->count();
                })
                ->all(),
        ];
    }
}
