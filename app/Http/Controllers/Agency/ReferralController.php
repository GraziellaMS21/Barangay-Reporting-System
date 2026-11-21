<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Agency Incoming Referrals', 'owner' => 'WS5']);
    }

    public function show(Referral $referral)
    {
        return view('stub', ['title' => "Referral: {$referral->reference_no}", 'owner' => 'WS5']);
    }

    public function update(Request $request, Referral $referral)
    {
        return redirect()->route('agency.referrals.show', $referral)->with('success', 'Referral status updated.');
    }

    public function message(Request $request, Referral $referral)
    {
        return redirect()->route('agency.referrals.show', $referral)->with('success', 'Message sent.');
    }
}
