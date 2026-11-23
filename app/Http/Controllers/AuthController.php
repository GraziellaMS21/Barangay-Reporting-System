<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt([...$credentials, 'is_active' => true], $remember)) {
            $request->session()->regenerate();

            if ($request->user()->isAgency()) {
                $intended = $request->session()->pull('url.intended');
                if ($intended && str_contains($intended, '/agency')) {
                    return redirect($intended);
                }

                return redirect()->route('agency.referrals.index');
            }

            $intended = $request->session()->pull('url.intended');
            if ($intended && ! str_contains($intended, '/agency')) {
                return redirect($intended);
            }

            return redirect()->route('admin.dashboard');
        }

        $user = User::where('email', $credentials['email'])->first();
        if ($user && ! $user->is_active && $user->isAgency() && Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors([
                    'email' => 'Your agency account request is currently pending review and verification by the Barangay Secretary.',
                ])
                ->onlyInput('email');
        }

        return back()
            ->withErrors([
                'email' => 'Invalid email or password.',
            ])
            ->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
