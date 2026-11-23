<?php

namespace App\Services;

use App\Models\BarangayCase;

class SummonsCounterService
{
    public function countForCase(BarangayCase $case): int
    {
        return $case->activeSummons()->count();
    }
}
