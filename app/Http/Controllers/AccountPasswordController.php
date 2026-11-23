<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\UpdateAccountPasswordRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountPasswordController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
    ) {}

    public function edit(): View
    {
        return view('account.password', [
            'user' => auth()->user(),
        ]);
    }

    public function update(UpdateAccountPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $user->update([
                'password' => Hash::make($request->validated('password')),
                'must_change_password' => false,
            ]);

            $this->audit->log($user, 'user.password_changed', $user, ['must_change_password' => true], ['must_change_password' => false]);
        }

        if ($user?->isAgency()) {
            return redirect()->route('agency.referrals.index')->with('success', 'Password updated successfully.');
        }

        return redirect()->route('admin.dashboard')->with('success', 'Password updated successfully.');
    }
}
