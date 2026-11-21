<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CaseFieldConfigController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Custom Case Form Fields', 'owner' => 'WS3']);
    }

    public function update(Request $request)
    {
        return redirect()->route('admin.case-fields.index')->with('success', 'Field configuration saved.');
    }
}
