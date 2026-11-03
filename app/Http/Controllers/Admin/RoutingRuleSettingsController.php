<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Office;
use App\Models\RoutingRule;
use App\Services\SettingsManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoutingRuleSettingsController extends Controller
{
    private const AUDITED_FIELDS = ['category_id', 'office_id', 'within_authority', 'jurisdiction_note'];

    public function index(): View
    {
        return view('admin.settings.routing-rules.index', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'offices' => Office::query()->orderBy('name')->get(['id', 'name']),
            'routingRules' => RoutingRule::query()
                ->with(['category:id,name', 'office:id,name'])
                ->orderBy('category_id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.settings.routing-rules.form', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'offices' => Office::query()->orderBy('name')->get(['id', 'name']),
            'routingRule' => new RoutingRule,
        ]);
    }

    public function edit(RoutingRule $routingRule): View
    {
        return view('admin.settings.routing-rules.form', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'offices' => Office::query()->orderBy('name')->get(['id', 'name']),
            'routingRule' => $routingRule,
        ]);
    }

    public function store(Request $request, SettingsManagementService $settingsManagementService): RedirectResponse
    {
        $rule = new RoutingRule;
        $settingsManagementService->save(
            $rule,
            $this->validatedAttributes($request),
            $request->user(),
            self::AUDITED_FIELDS
        );

        return redirect()->route('admin.settings.routing-rules.index')->with('success', 'Routing rule added.');
    }

    public function update(
        Request $request,
        RoutingRule $routingRule,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        $settingsManagementService->save(
            $routingRule,
            $this->validatedAttributes($request, $routingRule),
            $request->user(),
            self::AUDITED_FIELDS
        );

        return redirect()->route('admin.settings.routing-rules.index')->with('success', 'Routing rule updated.');
    }

    public function destroy(
        Request $request,
        RoutingRule $routingRule,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        $settingsManagementService->delete($routingRule, $request->user(), self::AUDITED_FIELDS);

        return redirect()->route('admin.settings.routing-rules.index')->with('success', 'Routing rule deleted.');
    }

    /**
     * @return array{category_id: int, office_id: int, within_authority: bool, jurisdiction_note: string|null}
     */
    private function validatedAttributes(Request $request, ?RoutingRule $routingRule = null): array
    {
        $categoryRule = Rule::unique('routing_rules', 'category_id');
        if ($routingRule !== null) {
            $categoryRule->ignore($routingRule->id);
        }

        $attributes = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id', $categoryRule],
            'office_id' => ['required', 'integer', 'exists:offices,id'],
            'within_authority' => ['required', 'boolean'],
            'jurisdiction_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'category_id.unique' => 'A routing rule already exists for this category.',
            'jurisdiction_note.max' => 'The jurisdiction note cannot exceed 2000 characters.',
        ]);
        $attributes['category_id'] = (int) $attributes['category_id'];
        $attributes['office_id'] = (int) $attributes['office_id'];
        $attributes['within_authority'] = $request->boolean('within_authority');

        return $attributes;
    }
}
