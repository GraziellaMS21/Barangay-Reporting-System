<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EscalationDestination;
use Illuminate\Http\Request;

class EscalationDestinationController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Escalation Destinations', 'owner' => 'WS4']);
    }

    public function create()
    {
        return view('stub', ['title' => 'Add Destination', 'owner' => 'WS4']);
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.escalation-destinations.index')->with('success', 'Destination created.');
    }

    public function show(EscalationDestination $escalation_destination)
    {
        return view('stub', ['title' => "Destination: {$escalation_destination->name}", 'owner' => 'WS4']);
    }

    public function edit(EscalationDestination $escalation_destination)
    {
        return view('stub', ['title' => "Edit Destination: {$escalation_destination->name}", 'owner' => 'WS4']);
    }

    public function update(Request $request, EscalationDestination $escalation_destination)
    {
        return redirect()->route('admin.escalation-destinations.index')->with('success', 'Destination updated.');
    }

    public function destroy(EscalationDestination $escalation_destination)
    {
        return redirect()->route('admin.escalation-destinations.index')->with('success', 'Destination deleted.');
    }
}
