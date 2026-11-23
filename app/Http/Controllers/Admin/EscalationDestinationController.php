<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EscalationDestination;
use App\Models\Office;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EscalationDestinationController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $destinations = EscalationDestination::query()
            ->with('office')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.case-actions.destinations.index', [
            'destinations' => $destinations,
        ]);
    }

    public function create(): View
    {
        $offices = Office::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.case-actions.destinations.create', [
            'offices' => $offices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $destination = EscalationDestination::create([
            'name' => $validated['name'],
            'office_id' => $validated['office_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'is_confirmed' => true,
        ]);

        $this->auditLogger->log(
            $request->user(),
            'escalation_destination.created',
            $destination,
            [],
            $destination->toArray()
        );

        return redirect()->route('admin.escalation-destinations.index')
            ->with('success', "Escalation destination '{$destination->name}' added.");
    }

    public function edit(EscalationDestination $escalation_destination): View
    {
        $offices = Office::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.case-actions.destinations.edit', [
            'destination' => $escalation_destination,
            'offices' => $offices,
        ]);
    }

    public function update(Request $request, EscalationDestination $escalation_destination): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $old = $escalation_destination->only(['name', 'office_id', 'sort_order', 'is_active']);

        $escalation_destination->update([
            'name' => $validated['name'],
            'office_id' => $validated['office_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogger->log(
            $request->user(),
            'escalation_destination.updated',
            $escalation_destination,
            $old,
            $escalation_destination->only(['name', 'office_id', 'sort_order', 'is_active'])
        );

        return redirect()->route('admin.escalation-destinations.index')
            ->with('success', "Escalation destination '{$escalation_destination->name}' updated.");
    }

    public function destroy(Request $request, EscalationDestination $escalation_destination): RedirectResponse
    {
        if ($escalation_destination->escalations()->exists()) {
            return back()->with('error', 'Cannot delete destination because cases have been escalated to it.');
        }

        $name = $escalation_destination->name;
        $escalation_destination->delete();

        $this->auditLogger->log(
            $request->user(),
            'escalation_destination.deleted',
            $escalation_destination,
            ['name' => $name],
            []
        );

        return redirect()->route('admin.escalation-destinations.index')
            ->with('success', "Destination '{$name}' deleted.");
    }
}
