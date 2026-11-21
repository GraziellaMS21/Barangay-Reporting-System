<?php

namespace App\Services;

use App\Exceptions\FiscalYearLocked;
use App\Exceptions\NoActiveFiscalYear;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FiscalYearService
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    public function active(): ?FiscalYear
    {
        return FiscalYear::where('status', 'active')->first();
    }

    public function forNewRecord(): FiscalYear
    {
        $active = $this->active();
        if (! $active) {
            throw new NoActiveFiscalYear('No active fiscal year found for new records.');
        }

        if ($active->is_locked) {
            throw new FiscalYearLocked('Active fiscal year is locked.');
        }

        return $active;
    }

    public function create(int $year, ?int $startMonth = null): FiscalYear
    {
        $startsOn = Carbon::create($year, $startMonth ?? 1, 1)->toDateString();
        $endsOn = Carbon::create($year, $startMonth ?? 1, 1)->addYear()->subDay()->toDateString();

        return FiscalYear::create([
            'year' => $year,
            'label' => "FY {$year}",
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'status' => 'inactive',
            'is_locked' => false,
        ]);
    }

    public function activate(FiscalYear $fy): FiscalYear
    {
        return DB::transaction(function () use ($fy) {
            FiscalYear::where('status', 'active')->where('id', '!=', $fy->id)->update(['status' => 'inactive']);
            $fy->update([
                'status' => 'active',
                'is_locked' => false,
            ]);

            return $fy->fresh();
        });
    }

    public function close(FiscalYear $fy, User $by, string $note): FiscalYear
    {
        $old = [
            'status' => $fy->status,
            'is_locked' => $fy->is_locked,
        ];

        $fy->update([
            'status' => 'closed',
            'is_locked' => true,
            'closed_by' => $by->id,
            'closed_at' => now(),
            'close_note' => $note,
        ]);

        $this->audit->log($by, 'fiscal_year.closed', $fy, $old, [
            'status' => 'closed',
            'is_locked' => true,
            'note' => $note,
        ]);

        return $fy->fresh();
    }

    public function reopen(FiscalYear $fy, User $by, string $note): FiscalYear
    {
        $old = [
            'status' => $fy->status,
            'is_locked' => $fy->is_locked,
        ];

        $fy->update([
            'status' => 'inactive',
            'is_locked' => false,
            'close_note' => $note,
        ]);

        $this->audit->log($by, 'fiscal_year.reopened', $fy, $old, [
            'status' => 'inactive',
            'is_locked' => false,
            'note' => $note,
        ]);

        return $fy->fresh();
    }
}
