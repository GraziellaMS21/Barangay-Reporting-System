<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PendingConfirmationController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Pending Confirmations', 'owner' => 'WS1']);
    }

    public function confirm(Request $request)
    {
        return redirect()->route('admin.pending.index')->with('success', 'Pending item confirmed.');
    }
}
