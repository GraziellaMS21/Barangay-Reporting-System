<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Response Templates', 'owner' => 'WS1']);
    }

    public function create()
    {
        return view('stub', ['title' => 'Create Response Template', 'owner' => 'WS1']);
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.templates.index')->with('success', 'Template created.');
    }

    public function edit(string $template)
    {
        return view('stub', ['title' => 'Edit Response Template', 'owner' => 'WS1']);
    }

    public function update(Request $request, string $template)
    {
        return redirect()->route('admin.templates.index')->with('success', 'Template updated.');
    }

    public function destroy(string $template)
    {
        return redirect()->route('admin.templates.index')->with('success', 'Template deleted.');
    }
}
