<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseFieldConfig;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseFieldConfigController extends Controller
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    /**
     * Display configuration table of form fields.
     */
    public function index(): View
    {
        $configsByEntity = CaseFieldConfig::query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('entity');

        return view('admin.settings.case-fields.index', compact('configsByEntity'));
    }

    /**
     * Update field visibility and requirements.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fields' => ['required', 'array'],
            'fields.*.id' => ['required', 'exists:case_field_configs,id'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.is_visible' => ['nullable', 'boolean'],
            'fields.*.applies_to' => ['required', 'in:all,resident,non_resident'],
        ]);

        $updatedCount = 0;
        foreach ($validated['fields'] as $fieldInput) {
            $config = CaseFieldConfig::find($fieldInput['id']);
            if ($config) {
                $config->update([
                    'is_required' => (bool) ($fieldInput['is_required'] ?? false),
                    'is_visible' => (bool) ($fieldInput['is_visible'] ?? false),
                    'applies_to' => $fieldInput['applies_to'],
                ]);
                $updatedCount++;
            }
        }

        $this->audit->log($request->user(), 'case_fields.updated', 'case_field_configs', [], [
            'updated_count' => $updatedCount,
        ]);

        return redirect()->route('admin.case-fields.index')->with('success', 'Case form field configurations successfully saved.');
    }
}
