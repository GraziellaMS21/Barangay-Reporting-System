<?php

namespace App\Services;

use App\Models\BarangayCase;
use App\Models\Party;
use App\Models\Personnel;
use App\Models\Referral;
use App\Models\Report;
use App\Models\User;

class GlobalSearchService
{
    /**
     * Searches across all fiscal years by case number, tracking code, party name, personnel, referrals.
     *
     * @return array{reports: array, referrals: array, cases: array, parties: array, personnel: array}
     */
    public function search(string $q, User $by): array
    {
        $q = trim($q);
        if ($q === '') {
            return [
                'reports' => [],
                'referrals' => [],
                'cases' => [],
                'parties' => [],
                'personnel' => [],
            ];
        }

        $strippedQ = str_replace('-', '', $q);

        // 1. Reports (across all fiscal years)
        $reports = Report::with(['category', 'fiscalYear'])
            ->where(function ($query) use ($q) {
                $query->where('tracking_code', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            })
            ->latest()
            ->limit(5)
            ->get();

        // 2. Referrals (across all fiscal years)
        $referrals = Referral::with(['office', 'fiscalYear', 'report'])
            ->where(function ($query) use ($q) {
                $query->where('reference_no', 'like', "%{$q}%")
                    ->orWhere('recipient_name', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%");
            })
            ->latest()
            ->limit(5)
            ->get();

        // 3. Cases (across all fiscal years, respecting confidentiality permissions)
        $casesQuery = BarangayCase::with(['caseType', 'fiscalYear']);
        if (! $by->hasPermission('cases.view_confidential')) {
            $casesQuery->where('is_confidential', false);
        }

        $cases = $casesQuery->where(function ($query) use ($q, $strippedQ) {
            $query->where('case_number', 'like', "%{$q}%")
                ->orWhere('title', 'like', "%{$q}%")
                ->orWhere('narrative', 'like', "%{$q}%");
            if (strlen($strippedQ) >= 4) {
                $query->orWhereRaw("REPLACE(case_number, '-', '') LIKE ?", ["%{$strippedQ}%"]);
            }
        })
            ->latest()
            ->limit(5)
            ->get();

        // 4. Parties
        $parties = Party::with('purok')
            ->where(function ($query) use ($q) {
                $query->where('full_name', 'like', "%{$q}%")
                    ->orWhere('alias', 'like', "%{$q}%")
                    ->orWhere('contact_number', 'like', "%{$q}%")
                    ->orWhere('address_line', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get();

        // 5. Personnel
        $personnel = Personnel::with('position')
            ->where(function ($query) use ($q) {
                $query->where('full_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('contact_number', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get();

        return [
            'reports' => $reports->toArray(),
            'referrals' => $referrals->toArray(),
            'cases' => $cases->toArray(),
            'parties' => $parties->toArray(),
            'personnel' => $personnel->toArray(),
        ];
    }
}
