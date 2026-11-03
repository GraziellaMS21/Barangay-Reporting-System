<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Office;
use App\Services\SettingsManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategorySettingsController extends Controller
{
    private const AUDITED_FIELDS = ['name', 'slug', 'risk_weight', 'default_office_id', 'is_active'];

    public function index(): View
    {
        return view('admin.settings.categories.index', [
            'categories' => Category::query()->with('defaultOffice')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.settings.categories.form', [
            'category' => new Category,
            'offices' => Office::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Category $category): View
    {
        return view('admin.settings.categories.form', [
            'category' => $category,
            'offices' => Office::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, SettingsManagementService $settingsManagementService): RedirectResponse
    {
        $category = new Category;
        $settingsManagementService->save(
            $category,
            $this->validatedAttributes($request),
            $request->user(),
            self::AUDITED_FIELDS
        );

        return redirect()->route('admin.settings.categories.index')->with('success', 'Category added.');
    }

    public function update(
        Request $request,
        Category $category,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        $settingsManagementService->save(
            $category,
            $this->validatedAttributes($request, $category),
            $request->user(),
            self::AUDITED_FIELDS
        );

        return redirect()->route('admin.settings.categories.index')->with('success', 'Category updated.');
    }

    /**
     * @return array{name: string, slug: string, risk_weight: int, default_office_id: int|null, is_active: bool}
     */
    private function validatedAttributes(Request $request, ?Category $category = null): array
    {
        $name = $request->input('name');
        $request->merge(['slug' => is_string($name) ? Str::slug($name) : '']);

        $slugRule = Rule::unique('categories', 'slug');
        if ($category !== null) {
            $slugRule->ignore($category->id);
        }

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $slugRule],
            'risk_weight' => ['required', 'integer', 'min:0', 'max:1000'],
            'default_office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'is_active' => ['required', 'boolean'],
        ], [
            'slug.unique' => 'A category with this name already exists.',
            'risk_weight.integer' => 'The risk weight must be a whole number.',
            'risk_weight.min' => 'The risk weight cannot be negative.',
            'risk_weight.max' => 'The risk weight cannot exceed 1000.',
        ]);

        if ($attributes['slug'] === '') {
            throw ValidationException::withMessages([
                'name' => 'Enter a category name containing letters or numbers.',
            ]);
        }

        $attributes['risk_weight'] = (int) $attributes['risk_weight'];
        $attributes['default_office_id'] = $attributes['default_office_id'] === null
            ? null
            : (int) $attributes['default_office_id'];
        $attributes['is_active'] = $request->boolean('is_active');

        return $attributes;
    }
}
