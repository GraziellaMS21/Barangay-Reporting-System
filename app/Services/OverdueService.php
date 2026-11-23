<?php

namespace App\Services;

use App\Models\BarangayCase;
use App\Support\CaseStatus;
use App\Support\WorkingDays;
use Carbon\CarbonImmutable;

class OverdueService
{
    public function __construct(
        protected SettingsService $settings
    ) {}

    /**
     * @return array{is_overdue: bool, days_overdue: int, deadline_on: ?string, rule: ?string, day: int}
     */
    public function forCase(BarangayCase $case): array
    {
        $holidays = $this->holidayDates();

        $terminal = in_array($case->status, [
            CaseStatus::SETTLED,
            CaseStatus::DISMISSED,
            CaseStatus::WITHDRAWN,
            CaseStatus::ESCALATED,
        ], true);

        if ($terminal) {
            return [
                'is_overdue' => false,
                'days_overdue' => 0,
                'deadline_on' => null,
                'rule' => null,
                'day' => 0,
            ];
        }

        $startDate = $case->filed_on ?? $case->created_at;
        $extension = (int) ($case->conciliation_extension_days ?? 0);

        if ($case->status === CaseStatus::UNSETTLED) {
            $base = $case->endorsed_to_pangkat_on
                ?? $case->complaint_started_on
                ?? $case->filed_on
                ?? $case->created_at;
            $days = 15 + $extension;
            $rule = 'conciliation';
        } else {
            $base = $startDate;
            $days = 15;
            $rule = 'mediation';
        }

        $deadline = WorkingDays::addWorkingDays($base, $days, $holidays);
        $today = CarbonImmutable::today();

        $isOverdue = $today->greaterThan($deadline);
        $daysOverdue = $isOverdue
            ? WorkingDays::countWorkingDays($deadline->addDay(), $today, $holidays)
            : 0;

        $dayNumber = WorkingDays::countWorkingDays($base, $today, $holidays);
        if ($dayNumber < 1) {
            $dayNumber = 1;
        }
        if ($dayNumber > $days) {
            $dayNumber = $days;
        }

        return [
            'is_overdue' => $isOverdue,
            'days_overdue' => $daysOverdue,
            'deadline_on' => $deadline->toDateString(),
            'rule' => $rule.' '.$days.' working days',
            'day' => $dayNumber,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function holidayDates(): array
    {
        $raw = (array) setting('calendar.holidays', []);
        $dates = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $dates[] = (string) ($item['date'] ?? '');
            } else {
                $dates[] = (string) $item;
            }
        }

        return array_values(array_filter($dates));
    }
}
