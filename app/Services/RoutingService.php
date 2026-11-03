<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Office;
use App\Models\Referral;
use App\Models\Report;
use App\Models\RoutingRule;

class RoutingService
{
    public function recommendedOffice(Category $category): ?Office
    {
        $rule = RoutingRule::query()
            ->where('category_id', $category->id)
            ->first();

        return $rule?->office ?? $category->defaultOffice;
    }

    public function decisionFor(Report $report): array
    {
        $category = $report->category;
        if (! $category) {
            return [
                'office' => null,
                'within_authority' => false,
                'jurisdiction_note' => 'Category not yet assigned.',
            ];
        }

        $rule = RoutingRule::query()->where('category_id', $category->id)->first();
        $office = $rule?->office ?? $category->defaultOffice;

        return [
            'office' => $office,
            'within_authority' => (bool) ($rule?->within_authority ?? true),
            'jurisdiction_note' => $rule?->jurisdiction_note ?? 'Trip within barangay authority.',
        ];
    }

    public function createReferral(Report $report, ?Office $office = null): ?Referral
    {
        $office ??= $this->recommendedOffice($report->category ?? throw new \RuntimeException('Report must include a category to route.'));

        if (! $office) {
            return null;
        }

        return Referral::firstOrCreate(
            [
                'report_id' => $report->id,
                'office_id' => $office->id,
            ],
            [
                'referral_text' => 'This report falls outside the barangay authority and is referred to '.$office->name.'.',
                'status' => 'pending',
            ]
        );
    }
}
