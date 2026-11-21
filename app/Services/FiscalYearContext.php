<?php

namespace App\Services;

use App\Models\FiscalYear;
use Illuminate\Support\Facades\Session;

class FiscalYearContext
{
    public const SESSION_KEY = 'fiscal_year.viewing_id';

    public function __construct(
        protected FiscalYearService $fiscalYearService
    ) {}

    public function viewing(): ?FiscalYear
    {
        if (Session::has(self::SESSION_KEY)) {
            $id = Session::get(self::SESSION_KEY);
            if ($id === null) {
                return null; // All years
            }

            return FiscalYear::find($id);
        }

        // Default to active fiscal year
        return $this->fiscalYearService->active();
    }

    public function setViewing(?int $fiscalYearId): void
    {
        Session::put(self::SESSION_KEY, $fiscalYearId);
    }
}
