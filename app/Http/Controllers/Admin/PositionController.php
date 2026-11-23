<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\People\SavePositionRequest;
use App\Models\Position;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
    ) {}

    public function index(): View
    {
        $positions = Position::query()
            ->withCount('personnel')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $unconfirmedCount = $positions->where('is_confirmed', false)->count();

        return view('admin.positions.index', compact('positions', 'unconfirmedCount'));
    }

    public function create(): View
    {
        return view('admin.positions.form', [
            'position' => new Position([
                'sort_order' => ((int) Position::max('sort_order')) + 1,
                'is_active' => true,
                'is_signatory_default' => false,
            ]),
        ]);
    }

    public function store(SavePositionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $position = Position::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_signatory_default' => $request->boolean('is_signatory_default'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'is_confirmed' => $request->boolean('is_confirmed', false),
        ]);

        $this->audit->log($request->user(), 'position.created', $position, [], $position->toArray());

        return redirect()->route('admin.positions.index')->with('success', 'Position created.');
    }

    public function show(Position $position): View
    {
        $position->load('personnel.user');

        return view('admin.positions.form', compact('position'));
    }

    public function edit(Position $position): View
    {
        return view('admin.positions.form', compact('position'));
    }

    public function update(SavePositionRequest $request, Position $position): RedirectResponse
    {
        $old = $position->toArray();
        $validated = $request->validated();

        $position->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'sort_order' => (int) ($validated['sort_order'] ?? $position->sort_order),
            'is_signatory_default' => $request->boolean('is_signatory_default'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $position->is_active,
            'is_confirmed' => $request->has('is_confirmed') ? $request->boolean('is_confirmed') : $position->is_confirmed,
        ]);

        $this->audit->log($request->user(), 'position.updated', $position, $old, $position->fresh()->toArray());

        return redirect()->route('admin.positions.index')->with('success', 'Position updated.');
    }

    public function toggleActive(Request $request, Position $position): RedirectResponse
    {
        $old = ['is_active' => $position->is_active];
        $newState = ! $position->is_active;
        $position->update(['is_active' => $newState]);

        $this->audit->log($request->user(), 'position.status_changed', $position, $old, ['is_active' => $newState]);

        return redirect()->route('admin.positions.index')
            ->with('success', "Position {$position->name} ".($newState ? 'activated.' : 'deactivated.'));
    }

    public function destroy(Request $request, Position $position): RedirectResponse
    {
        if ($position->personnel()->exists() || $position->signatoryAssignments()->exists()) {
            return redirect()->route('admin.positions.index')
                ->with('error', 'Cannot delete a position assigned to personnel or signatory roles. Deactivate it instead.');
        }

        $old = $position->toArray();
        $position->delete();
        $this->audit->log($request->user(), 'position.deleted', $position, $old, []);

        return redirect()->route('admin.positions.index')->with('success', 'Position deleted.');
    }
}
