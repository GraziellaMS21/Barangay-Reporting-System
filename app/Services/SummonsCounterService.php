<?php

namespace App\Services;

use App\Models\BarangayCase;

class SummonsCounterService
{
    public function __construct(
        protected SettingsService $settings
    ) {}

    /**
     * Sequential summons count for display only. No threshold is enforced:
     * the barangay counts days, not summons (Secretary Q20). Deadlines are handled by OverdueService.
     *
     * @return array{issued: int, limit: ?int, remaining: ?int, eligible: bool}
     */
    public function forCase(BarangayCase $case): array
    {
        $rawLimit = $this->settings->get('cases.summons_limit', 3);
        $limit = ($rawLimit !== null && $rawLimit !== '') ? (int) $rawLimit : 3;
        $issued = method_exists($case, 'activeSummons')
            ? $case->activeSummons()->count()
            : $case->summons()->whereNull('voided_at')->count();
        $remaining = $limit !== null ? max(0, $limit - $issued) : null;

        return [
            'issued' => $issued,
            'limit' => $limit,
            'remaining' => $remaining,
            'eligible' => false,
        ];
    }
}
