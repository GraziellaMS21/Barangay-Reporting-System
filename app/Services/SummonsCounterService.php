<?php

namespace App\Services;

use App\Models\BarangayCase;

class SummonsCounterService
{
    public function __construct(
        protected SettingsService $settings
    ) {}

    /**
     * STUB: WS4 implements full summons counter.
     * Rule: issued counts non-voided summons for the case;
     * eligible = issued >= setting('cases.summons_limit', 3) && case status not in (settled, escalated, dismissed, withdrawn)
     *
     * @return array{issued: int, limit: int, remaining: int, eligible: bool}
     */
    public function forCase(BarangayCase $case): array
    {
        $limit = $this->settings->getInt('cases.summons_limit', 3);
        $issued = $case->activeSummons()->count();
        $remaining = max(0, $limit - $issued);
        $terminal = in_array($case->status, ['settled', 'escalated', 'dismissed', 'withdrawn'], true);

        return [
            'issued' => $issued,
            'limit' => $limit,
            'remaining' => $remaining,
            'eligible' => ($issued >= $limit && ! $terminal),
        ];
    }
}
