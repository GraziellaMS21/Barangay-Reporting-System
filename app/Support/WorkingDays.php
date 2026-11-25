<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class WorkingDays
{
    /**
     * Resolve configured ISO-8601 working day numbers (1 = Mon ... 7 = Sun).
     *
     * @return array<int, int>
     */
    public static function configuredWorkingDays(): array
    {
        $settingVal = function_exists('setting') ? setting('cases.working_days') : null;

        if (is_string($settingVal) && trim($settingVal) !== '') {
            $parsed = array_values(array_filter(array_map('intval', explode(',', $settingVal)), fn (int $d): bool => $d >= 1 && $d <= 7));
            if ($parsed !== []) {
                return $parsed;
            }
        } elseif (is_array($settingVal) && $settingVal !== []) {
            $parsed = array_values(array_filter(array_map('intval', $settingVal), fn (int $d): bool => $d >= 1 && $d <= 7));
            if ($parsed !== []) {
                return $parsed;
            }
        }

        $configDays = (array) config('katarungang_pambarangay.working_days', [1, 2, 3, 4, 5]);

        return array_values(array_filter(array_map('intval', $configDays), fn (int $d): bool => $d >= 1 && $d <= 7));
    }

    /**
     * Determine if a date is a working day (configured weekdays and not in holidays).
     *
     * @param  array<int, string>  $holidays  Array of YYYY-MM-DD date strings
     * @param  array<int, int>|null  $workingDays  Optional ISO-8601 weekday numbers (1..7)
     */
    public static function isWorkingDay(CarbonInterface|string $date, array $holidays = [], ?array $workingDays = null): bool
    {
        $carbon = CarbonImmutable::parse($date)->startOfDay();
        $allowedDays = $workingDays ?? self::configuredWorkingDays();

        if (! in_array($carbon->dayOfWeekIso, $allowedDays, true)) {
            return false;
        }

        $formatted = $carbon->toDateString();

        return ! in_array($formatted, $holidays, true);
    }

    /**
     * Add N working days to a start date.
     *
     * @param  array<int, string>  $holidays
     * @param  array<int, int>|null  $workingDays
     */
    public static function addWorkingDays(CarbonInterface|string $startDate, int $days, array $holidays = [], ?array $workingDays = null): CarbonImmutable
    {
        $current = CarbonImmutable::parse($startDate)->startOfDay();
        $added = 0;

        while ($added < $days) {
            $current = $current->addDay();
            if (self::isWorkingDay($current, $holidays, $workingDays)) {
                $added++;
            }
        }

        return $current;
    }

    /**
     * Count working days between two dates.
     *
     * @param  array<int, string>  $holidays
     * @param  array<int, int>|null  $workingDays
     */
    public static function countWorkingDays(CarbonInterface|string $startDate, CarbonInterface|string $endDate, array $holidays = [], ?array $workingDays = null): int
    {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();

        if ($start->greaterThan($end)) {
            return 0;
        }

        $count = 0;
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            if (self::isWorkingDay($cursor, $holidays, $workingDays)) {
                $count++;
            }
            $cursor = $cursor->addDay();
        }

        return $count;
    }
}
