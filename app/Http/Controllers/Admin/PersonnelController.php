<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use Illuminate\Http\Request;

class PersonnelController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Barangay Personnel Directory', 'owner' => 'WS2']);
    }

    public function create()
    {
        return view('stub', ['title' => 'Add Personnel', 'owner' => 'WS2']);
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.personnel.index')->with('success', 'Personnel created.');
    }

    public function show(Personnel $personnel)
    {
        return view('stub', ['title' => "Personnel: {$personnel->full_name}", 'owner' => 'WS2']);
    }

    public function edit(Personnel $personnel)
    {
        return view('stub', ['title' => "Edit Personnel: {$personnel->full_name}", 'owner' => 'WS2']);
    }

    public function update(Request $request, Personnel $personnel)
    {
        return redirect()->route('admin.personnel.index')->with('success', 'Personnel updated.');
    }

    public function destroy(Personnel $personnel)
    {
        return redirect()->route('admin.personnel.index')->with('success', 'Personnel removed.');
    }
}
