<?php

namespace App\Services;

use App\Models\Office;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Derived, read-only workload counts for the concern queue.
 *
 * Counts are always calculated from reports and existing relationships. No
 * workload table or cached score is introduced. Closed statuses are excluded
 * from active workload; see Report::CLOSED_STATUSES.
 */
class WorkloadService
{
    /**
     * @return array{
     *     is_admin: bool,
     *     staff: list<array{id: int, name: string, count: int}>,
     *     offices: list<array{id: int, name: string, count: int}>,
     *     own: array{id: int, name: string, count: int}|null,
     *     unassigned: int
     * }
     */
    public function summary(User $viewer): array
    {
        $isAdmin = $viewer->isAdmin();

        $activeReports = Report::query()->whereNotIn('status', Report::CLOSED_STATUSES);

        $unassigned = (clone $activeReports)
            ->whereNull('assigned_to')
            ->count();

        $staffCounts = (clone $activeReports)
            ->whereNotNull('assigned_to')
            ->select('assigned_to', DB::raw('count(*) as aggregate'))
            ->groupBy('assigned_to')
            ->pluck('aggregate', 'assigned_to');

        $officeCounts = (clone $activeReports)
            ->whereNotNull('office_id')
            ->select('office_id', DB::raw('count(*) as aggregate'))
            ->groupBy('office_id')
            ->pluck('aggregate', 'office_id');

        $staffQuery = User::query()
            ->whereIn('role', ['admin', 'staff'])
            ->where('is_active', true);

        if (! $isAdmin) {
            $staffQuery->whereKey($viewer->id);
        }

        $staff = $staffQuery
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'count' => (int) $staffCounts->get($user->id, 0),
            ])
            ->values()
            ->all();

        $officeQuery = Office::query()->active()->internal();

        if (! $isAdmin) {
            if ($viewer->office_id === null) {
                $officeQuery->whereRaw('1 = 0');
            } else {
                $officeQuery->whereKey($viewer->office_id);
            }
        }

        $offices = $officeQuery
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Office $office): array => [
                'id' => $office->id,
                'name' => $office->name,
                'count' => (int) $officeCounts->get($office->id, 0),
            ])
            ->values()
            ->all();

        $own = $isAdmin
            ? null
            : [
                'id' => $viewer->id,
                'name' => $viewer->name,
                'count' => (int) $staffCounts->get($viewer->id, 0),
            ];

        return [
            'is_admin' => $isAdmin,
            'staff' => $staff,
            'offices' => $offices,
            'own' => $own,
            'unassigned' => $unassigned,
        ];
    }
}
