<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(): View
    {
        $referrals = Referral::query()->with(['report', 'office'])->latest()->paginate(15);

        return view('admin.referrals.index', compact('referrals'));
    }
}
