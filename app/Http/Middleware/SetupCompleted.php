<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class SetupCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $dbSetting = setting('system.setup_completed');

        if ($dbSetting !== null) {
            $isSetupCompleted = filter_var($dbSetting, FILTER_VALIDATE_BOOLEAN);
        } elseif (app()->environment('testing')) {
            $isSetupCompleted = (bool) config('app.setup_completed', true);
        } else {
            $isSetupCompleted = false;
        }

        if (! $isSetupCompleted) {
            // Exclude setup routes, health check, assets, and login/logout
            if ($request->is('setup', 'setup/*', 'up', 'build/*', 'vendor/*', 'assets/*', 'favicon.ico', 'login', 'logout')) {
                return $next($request);
            }

            if (Route::has('setup.show')) {
                return redirect()->route('setup.show');
            }

            return redirect('/setup');
        }

        // When setup is completed, requests to /setup redirect to login
        if ($request->is('setup', 'setup/*')) {
            if (Route::has('login')) {
                return redirect()->route('login');
            }

            return redirect('/login');
        }

        return $next($request);
    }
}
