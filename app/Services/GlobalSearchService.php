<?php

namespace App\Services;

use App\Models\User;

class GlobalSearchService
{
    /**
     * STUB: WS1 implements global search across models.
     *
     * @return array{reports: array, referrals: array, cases: array, parties: array, personnel: array}
     */
    public function search(string $q, User $by): array
    {
        return [
            'reports' => [],
            'referrals' => [],
            'cases' => [],
            'parties' => [],
            'personnel' => [],
        ];
    }
}
