<?php

namespace Tests\Unit;

use App\Support\WorkingDays;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WorkingDaysTest extends TestCase
{
    protected bool $seedSetup = false;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forever('setting.cases.working_days', '');
    }

    public function test_identifies_weekends_correctly(): void
    {
        // 2026-11-20 is Friday, 2026-11-21 is Saturday, 2026-11-22 is Sunday, 2026-11-23 is Monday
        $this->assertTrue(WorkingDays::isWorkingDay('2026-11-20'));
        $this->assertFalse(WorkingDays::isWorkingDay('2026-11-21'));
        $this->assertFalse(WorkingDays::isWorkingDay('2026-11-22'));
        $this->assertTrue(WorkingDays::isWorkingDay('2026-11-23'));
    }

    public function test_respects_holiday_dates(): void
    {
        $holidays = ['2026-11-23']; // Monday as a holiday
        $this->assertFalse(WorkingDays::isWorkingDay('2026-11-23', $holidays));
        $this->assertTrue(WorkingDays::isWorkingDay('2026-11-24', $holidays));
    }

    public function test_adds_working_days_skipping_weekends_and_holidays(): void
    {
        // Start Friday 2026-11-20. Add 1 working day -> Monday 2026-11-23
        $result = WorkingDays::addWorkingDays('2026-11-20', 1);
        $this->assertSame('2026-11-23', $result->toDateString());

        // With holiday on Monday 2026-11-23 -> Tuesday 2026-11-24
        $resultWithHoliday = WorkingDays::addWorkingDays('2026-11-20', 1, ['2026-11-23']);
        $this->assertSame('2026-11-24', $resultWithHoliday->toDateString());

        // 15 working days from Monday 2026-11-02 -> Friday 2026-11-20
        $fifteenDays = WorkingDays::addWorkingDays('2026-11-02', 14); // 14 steps = 15th day
        $this->assertSame('2026-11-20', $fifteenDays->toDateString());
    }

    public function test_counts_working_days_between_dates(): void
    {
        // Monday to Friday = 5 working days
        $count = WorkingDays::countWorkingDays('2026-11-16', '2026-11-20');
        $this->assertSame(5, $count);

        // Saturday to Sunday = 0 working days
        $countWeekend = WorkingDays::countWorkingDays('2026-11-21', '2026-11-22');
        $this->assertSame(0, $countWeekend);

        // Monday to Friday with Wednesday holiday = 4 working days
        $countWithHoliday = WorkingDays::countWorkingDays('2026-11-16', '2026-11-20', ['2026-11-18']);
        $this->assertSame(4, $countWithHoliday);
    }
}
