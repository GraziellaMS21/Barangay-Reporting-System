<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function show(string $token)
    {
        return view('stub', ['title' => 'Agency Team Invitation', 'owner' => 'WS5']);
    }

    public function accept(Request $request, string $token)
    {
        return redirect()->route('agency.referrals.index');
    }
}
