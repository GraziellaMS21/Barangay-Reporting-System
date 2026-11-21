<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GeneralSettingsController extends Controller
{
    public function edit()
    {
        return view('stub', ['title' => 'General Settings', 'owner' => 'WS1']);
    }

    public function update(Request $request)
    {
        return redirect()->route('admin.settings.general.edit')->with('success', 'General settings saved.');
    }
}
