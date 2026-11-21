<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        // Support comma-separated slugs in parameter
        $slugs = [];
        foreach ($permissions as $item) {
            foreach (explode(',', $item) as $slug) {
                $trimmed = trim($slug);
                if ($trimmed !== '') {
                    $slugs[] = $trimmed;
                }
            }
        }

        foreach ($slugs as $slug) {
            if ($user->hasPermission($slug)) {
                return $next($request);
            }
        }

        abort(403, 'Forbidden. Required permission missing.');
    }
}
