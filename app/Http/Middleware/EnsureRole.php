<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Unauthorized.');
        }

        $allowedRoles = [];
        foreach ($roles as $roleGroup) {
            foreach (explode(',', $roleGroup) as $r) {
                $r = trim($r);
                if ($r === 'staff') {
                    // role:staff admits staff and admin, never agency
                    $allowedRoles[] = 'staff';
                    $allowedRoles[] = 'admin';
                } else {
                    $allowedRoles[] = $r;
                }
            }
        }

        if (! in_array($user->role, array_unique($allowedRoles), true)) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
