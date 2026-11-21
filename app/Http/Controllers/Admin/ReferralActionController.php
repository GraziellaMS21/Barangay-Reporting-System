<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;

class ReferralActionController extends Controller
{
    public function send(Request $request, Referral $referral)
    {
        return redirect()->route('admin.referrals.show', $referral)->with('success', 'Referral transmitted.');
    }

    public function storeMessage(Request $request, Referral $referral)
    {
        return redirect()->route('admin.referrals.show', $referral)->with('success', 'Message logged.');
    }
}
