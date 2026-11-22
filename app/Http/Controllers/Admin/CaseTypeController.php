<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CaseTypeController extends Controller
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    /**
     * Display a listing of case classifications.
     */
    public function index(): View
    {
        $caseTypes = CaseType::query()
            ->withCount('cases')
            ->orderBy('sort_order')
            ->paginate(20);

        return view('admin.settings.case-types.index', compact('caseTypes'));
    }

    /**
     * Show form for creating a new case type.
     */
    public function create(): View
    {
        return view('admin.settings.case-types.create');
    }

    /**
     * Store a newly created case type.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:case_types,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        if (CaseType::where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(4));
        }

        $caseType = CaseType::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'is_confirmed' => false,
        ]);

        $this->audit->log($request->user(), 'case_type.created', $caseType, [], $caseType->toArray());

        return redirect()->route('admin.case-types.index')->with('success', "Case classification '{$caseType->name}' created.");
    }

    /**
     * Show form for editing the case type.
     */
    public function edit(CaseType $case_type): View
    {
        return view('admin.settings.case-types.edit', compact('case_type'));
    }

    /**
     * Update the specified case type.
     */
    public function update(Request $request, CaseType $case_type): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:case_types,name,'.$case_type->id],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $old = $case_type->only(['name', 'description', 'sort_order', 'is_active']);
        $case_type->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->audit->log($request->user(), 'case_type.updated', $case_type, $old, $case_type->only(array_keys($old)));

        return redirect()->route('admin.case-types.index')->with('success', "Case classification '{$case_type->name}' updated.");
    }

    /**
     * Remove the specified case type if no cases are associated with it.
     */
    public function destroy(Request $request, CaseType $case_type): RedirectResponse
    {
        if ($case_type->cases()->exists()) {
            return back()->withErrors(['error' => "Cannot delete '{$case_type->name}' because existing blotter cases are linked to it."]);
        }

        $oldName = $case_type->name;
        $case_type->delete();

        $this->audit->log($request->user(), 'case_type.deleted', 'case_type', ['name' => $oldName], []);

        return redirect()->route('admin.case-types.index')->with('success', "Case classification '{$oldName}' deleted.");
    }
}
