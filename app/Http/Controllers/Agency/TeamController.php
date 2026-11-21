<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Agency Team Members', 'owner' => 'WS5']);
    }

    public function invite(Request $request)
    {
        return redirect()->route('agency.team.index')->with('success', 'Invitation sent.');
    }

    public function revoke(Request $request)
    {
        return redirect()->route('agency.team.index')->with('success', 'Invitation revoked.');
    }

    public function deactivate(Request $request)
    {
        return redirect()->route('agency.team.index')->with('success', 'Member deactivated.');
    }
}
