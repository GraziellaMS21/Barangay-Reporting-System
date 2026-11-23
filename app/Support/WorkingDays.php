<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class WorkingDays
{
    /**
     * Determine if a date is a working day (Mon-Fri and not in holidays).
     *
     * @param  array<int, string>  $holidays  Array of YYYY-MM-DD date strings
     */
    public static function isWorkingDay(CarbonInterface|string $date, array $holidays = []): bool
    {
        $carbon = CarbonImmutable::parse($date)->startOfDay();

        // 6 = Saturday, 7 = Sunday
        if ($carbon->isWeekend()) {
            return false;
        }

        $formatted = $carbon->toDateString();

        return ! in_array($formatted, $holidays, true);
    }

    /**
     * Add N working days to a start date.
     *
     * @param  array<int, string>  $holidays
     */
    public static function addWorkingDays(CarbonInterface|string $startDate, int $days, array $holidays = []): CarbonImmutable
    {
        $current = CarbonImmutable::parse($startDate)->startOfDay();
        $added = 0;

        while ($added < $days) {
            $current = $current->addDay();
            if (self::isWorkingDay($current, $holidays)) {
                $added++;
            }
        }

        return $current;
    }

    /**
     * Count working days between two dates.
     *
     * @param  array<int, string>  $holidays
     */
    public static function countWorkingDays(CarbonInterface|string $startDate, CarbonInterface|string $endDate, array $holidays = []): int
    {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();

        if ($start->greaterThan($end)) {
            return 0;
        }

        $count = 0;
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            if (self::isWorkingDay($cursor, $holidays)) {
                $count++;
            }
            $cursor = $cursor->addDay();
        }

        return $count;
    }
}
