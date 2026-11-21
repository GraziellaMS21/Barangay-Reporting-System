<?php

namespace App\Models\Concerns;

use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToFiscalYear
{
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function scopeForFiscalYear(Builder $query, ?FiscalYear $fy): Builder
    {
        if ($fy === null) {
            return $query;
        }

        return $query->where('fiscal_year_id', $fy->id);
    }
}
