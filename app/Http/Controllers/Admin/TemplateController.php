<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ResponseTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        $templates = ResponseTemplate::with(['category', 'creator'])
            ->orderBy('kind')
            ->orderBy('title')
            ->get();

        return view('admin.settings.templates.index', compact('templates'));
    }

    public function create(): View
    {
        $categories = Category::all();

        return view('admin.settings.templates.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'kind' => ['required', 'string', 'in:report_status,hearing_notice,referral_note,general'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ResponseTemplate::create([
            'title' => $validated['title'],
            'name' => $validated['name'] ?? $validated['title'],
            'kind' => $validated['kind'],
            'category_id' => $validated['category_id'] ?? null,
            'body' => $validated['body'],
            'is_active' => ! empty($validated['is_active']),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.templates.index')->with('success', 'Response template created.');
    }

    public function edit(string $template): View
    {
        $templateModel = ResponseTemplate::findOrFail($template);
        $categories = Category::all();

        return view('admin.settings.templates.edit', [
            'template' => $templateModel,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, string $template): RedirectResponse
    {
        $templateModel = ResponseTemplate::findOrFail($template);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'kind' => ['required', 'string', 'in:report_status,hearing_notice,referral_note,general'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $templateModel->update([
            'title' => $validated['title'],
            'name' => $validated['name'] ?? $validated['title'],
            'kind' => $validated['kind'],
            'category_id' => $validated['category_id'] ?? null,
            'body' => $validated['body'],
            'is_active' => ! empty($validated['is_active']),
        ]);

        return redirect()->route('admin.templates.index')->with('success', 'Response template updated.');
    }

    public function destroy(string $template): RedirectResponse
    {
        $templateModel = ResponseTemplate::findOrFail($template);
        $templateModel->delete();

        return redirect()->route('admin.templates.index')->with('success', 'Response template deleted.');
    }

    public function json(Request $request): JsonResponse
    {
        $kind = $request->query('kind');
        $query = ResponseTemplate::where('is_active', true);

        if ($kind) {
            $query->where('kind', $kind);
        }

        $templates = $query->get(['id', 'title', 'kind', 'body']);

        return response()->json($templates);
    }
}
