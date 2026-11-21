<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NumberingController extends Controller
{
    public function edit()
    {
        return view('stub', ['title' => 'Document Numbering Schemes', 'owner' => 'WS1']);
    }

    public function update(Request $request)
    {
        return redirect()->route('admin.numbering.edit')->with('success', 'Numbering schemes updated.');
    }
}
