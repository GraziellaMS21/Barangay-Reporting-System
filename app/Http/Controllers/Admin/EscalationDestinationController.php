<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EscalationDestination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EscalationDestinationController extends Controller
{
    public function index(): View
    {
        return $this->placeholder('Escalation destinations', 'Viewing and managing escalation destinations is not available yet.');
    }

    public function create(): View
    {
        return $this->placeholder('Add an escalation destination', 'Adding an escalation destination is not available yet.');
    }

    public function store(Request $request): View
    {
        return $this->placeholder('Destination not created', 'Escalation destination management is not available yet. No destination was saved.');
    }

    public function show(EscalationDestination $escalation_destination): View
    {
        return $this->placeholder(
            'Escalation destination',
            "Viewing destination {$escalation_destination->name} is not available yet."
        );
    }

    public function edit(EscalationDestination $escalation_destination): View
    {
        return $this->placeholder(
            'Edit escalation destination',
            "Editing destination {$escalation_destination->name} is not available yet."
        );
    }

    public function update(Request $request, EscalationDestination $escalation_destination): View
    {
        return $this->placeholder(
            'Destination not updated',
            "Escalation destination management is not available yet. {$escalation_destination->name} was not changed."
        );
    }

    public function destroy(EscalationDestination $escalation_destination): View
    {
        return $this->placeholder(
            'Destination not deleted',
            "Escalation destination management is not available yet. {$escalation_destination->name} was not deleted."
        );
    }

    private function placeholder(string $title, string $message): View
    {
        return view('admin.case-actions.placeholder', [
            'title' => $title,
            'message' => $message,
        ]);
    }
}
