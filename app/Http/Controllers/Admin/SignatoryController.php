<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SignatoryController extends Controller
{
    public function edit()
    {
        return view('stub', ['title' => 'Signatory Assignments', 'owner' => 'WS2']);
    }

    public function update(Request $request)
    {
        return redirect()->route('admin.signatories.edit')->with('success', 'Signatories updated.');
    }
}
