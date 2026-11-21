<?php

namespace App\Services;

use App\Models\BarangayCase;

class OverdueService
{
    /**
     * STUB: WS4 implements full overdue calculation.
     *
     * @return array{is_overdue: bool, days_overdue: int, deadline_on: ?string, rule: ?string}
     */
    public function forCase(BarangayCase $case): array
    {
        return [
            'is_overdue' => false,
            'days_overdue' => 0,
            'deadline_on' => null,
            'rule' => null,
        ];
    }
}
