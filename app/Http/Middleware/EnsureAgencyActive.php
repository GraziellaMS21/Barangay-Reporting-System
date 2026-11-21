<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgencyActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAgency()) {
            abort(403, 'Agency portal access only.');
        }

        $registration = $user->agencyRegistration;
        $isApproved = ($registration && $registration->status === 'approved');
        $isVerified = $user->hasVerifiedEmail();

        if (! $user->is_active || ! $isApproved || ! $isVerified) {
            if ($request->is('agency/pending', 'logout') || $request->routeIs('agency.pending', 'logout')) {
                return $next($request);
            }

            if (Route::has('agency.pending')) {
                return redirect()->route('agency.pending');
            }

            return redirect('/agency/pending');
        }

        return $next($request);
    }
}
