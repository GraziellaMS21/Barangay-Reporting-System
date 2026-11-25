<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purok;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurokController extends Controller
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    public function index(): View
    {
        $puroks = Purok::query()
            ->withCount('reports')
            ->orderBy('name')
            ->get();

        return view('admin.settings.puroks.index', compact('puroks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:puroks,name'],
        ]);

        $purok = Purok::create([
            'name' => trim($validated['name']),
        ]);

        $this->audit->log($request->user(), 'purok.created', $purok, [], $purok->toArray());

        return redirect()->route('admin.puroks.index')->with('success', "Purok / Zone \"{$purok->name}\" added.");
    }

    public function update(Request $request, Purok $purok): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('puroks', 'name')->ignore($purok->id)],
        ]);

        $old = $purok->only(['name']);
        $purok->update(['name' => trim($validated['name'])]);

        $this->audit->log($request->user(), 'purok.updated', $purok, $old, $purok->only(['name']));

        return redirect()->route('admin.puroks.index')->with('success', "Purok / Zone updated to \"{$purok->name}\".");
    }

    public function destroy(Request $request, Purok $purok): RedirectResponse
    {
        if ($purok->reports()->exists()) {
            return back()->with('error', "Cannot delete \"{$purok->name}\" because it is linked to existing reports.");
        }

        $old = $purok->toArray();
        $name = $purok->name;
        $purok->delete();

        $this->audit->log($request->user(), 'purok.deleted', $purok, $old, []);

        return redirect()->route('admin.puroks.index')->with('success', "Purok / Zone \"{$name}\" removed.");
    }
}
