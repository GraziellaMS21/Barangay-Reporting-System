<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Barangay Positions & Titles', 'owner' => 'WS2']);
    }

    public function create()
    {
        return view('stub', ['title' => 'Create Position', 'owner' => 'WS2']);
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.positions.index')->with('success', 'Position created.');
    }

    public function show(Position $position)
    {
        return view('stub', ['title' => "Position: {$position->name}", 'owner' => 'WS2']);
    }

    public function edit(Position $position)
    {
        return view('stub', ['title' => "Edit Position: {$position->name}", 'owner' => 'WS2']);
    }

    public function update(Request $request, Position $position)
    {
        return redirect()->route('admin.positions.index')->with('success', 'Position updated.');
    }

    public function destroy(Position $position)
    {
        return redirect()->route('admin.positions.index')->with('success', 'Position deleted.');
    }
}
