<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Office;
use App\Models\Referral;
use App\Models\Report;
use App\Models\RoutingRule;
use App\Models\User;
use Illuminate\Support\Collection;

class RoutingService
{
    public function __construct(private readonly ReferralService $referralService) {}

    public function recommendedOffice(Category $category): ?Office
    {
        $rule = RoutingRule::query()
            ->with('office')
            ->where('category_id', $category->id)
            ->first();

        $ruleOffice = $rule?->office;
        if ($ruleOffice && $ruleOffice->is_active) {
            return $ruleOffice;
        }

        $defaultOffice = $category->defaultOffice;
        if ($defaultOffice && $defaultOffice->is_active) {
            return $defaultOffice;
        }

        return null;
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return array<int, array{office_id: int|null, office_name: string|null, within_authority: bool, reason: string, default_assignee_id: int|null}>
     */
    public function decisionsForCategories(Collection $categories): array
    {
        $rules = RoutingRule::query()
            ->with('office')
            ->whereIn('category_id', $categories->modelKeys())
            ->get()
            ->keyBy('category_id');

        return $categories->mapWithKeys(function (Category $category) use ($rules): array {
            $rule = $rules->get($category->id);
            $candidate = $rule?->office ?? $category->defaultOffice;
            $office = ($candidate && $candidate->is_active)
                ? $candidate
                : (($category->defaultOffice && $category->defaultOffice->is_active) ? $category->defaultOffice : null);

            return [$category->id => [
                'office_id' => $office?->id,
                'office_name' => $office?->name,
                'within_authority' => (bool) ($rule?->within_authority ?? true),
                'reason' => $rule?->jurisdiction_note ?? 'Assigned to the category default office.',
                'default_assignee_id' => $office?->default_assignee_id,
            ]];
        })->all();
    }

    /**
     * @return array{office: Office|null, within_authority: bool, jurisdiction_note: string}
     */
    public function decisionForCategory(Category $category): array
    {
        $rule = RoutingRule::query()
            ->with('office')
            ->where('category_id', $category->id)
            ->first();
        $candidate = $rule?->office ?? $category->defaultOffice;
        $office = ($candidate && $candidate->is_active)
            ? $candidate
            : (($category->defaultOffice && $category->defaultOffice->is_active) ? $category->defaultOffice : null);

        return [
            'office' => $office,
            'within_authority' => (bool) ($rule?->within_authority ?? true),
            'jurisdiction_note' => $rule?->jurisdiction_note ?? 'Assigned to the category default office.',
        ];
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

        return $this->decisionForCategory($category);
    }

    /**
     * Legacy entry point for creating a referral. Delegates to ReferralService so
     * every referral gets a REF number, a real letter and the "prepared" status.
     */
    public function createReferral(Report $report, ?Office $office = null, ?User $user = null): ?Referral
    {
        $office ??= $this->recommendedOffice($report->category ?? throw new \RuntimeException('Report must include a category to route.'));

        if (! $office) {
            return null;
        }

        return $this->referralService->prepareReferral($report, $office, $user);
    }
}
