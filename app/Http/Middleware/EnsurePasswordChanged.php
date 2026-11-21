<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            if ($request->is('account/password*', 'logout') || $request->routeIs('account.password.edit', 'account.password.update', 'logout')) {
                return $next($request);
            }

            if (Route::has('account.password.edit')) {
                return redirect()->route('account.password.edit');
            }

            return redirect('/account/password');
        }

        return $next($request);
    }
}
