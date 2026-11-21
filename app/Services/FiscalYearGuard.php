<?php

namespace App\Services;

use App\Exceptions\FiscalYearLocked;
use App\Models\BarangayCase;
use App\Models\Referral;
use App\Models\Report;
use Illuminate\Database\Eloquent\Model;

class FiscalYearGuard
{
    /**
     * Assert that a record is writable.
     * Throws if the record is finalized and its fiscal year is locked.
     */
    public function assertWritable(Model $record): void
    {
        $fiscalYear = method_exists($record, 'fiscalYear') ? $record->fiscalYear : null;
        if (! $fiscalYear || ! $fiscalYear->is_locked) {
            return;
        }

        $isFinalized = false;

        if ($record instanceof Report) {
            $isFinalized = in_array($record->status, ['resolved', 'no_action', 'merged'], true);
        } elseif ($record instanceof Referral) {
            $isFinalized = ($record->status === 'closed');
        } elseif ($record instanceof BarangayCase) {
            $isFinalized = in_array($record->status, ['settled', 'dismissed', 'withdrawn', 'escalated'], true);
        }

        if ($isFinalized) {
            throw new FiscalYearLocked("Cannot modify a finalized record in locked fiscal year {$fiscalYear->year}.");
        }
    }
}
