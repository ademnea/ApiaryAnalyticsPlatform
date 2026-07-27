<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotFarmer
{
    /**
     * Handle an incoming request.
     *
     * Blocks access to admin routes only if the user's ONLY role is 'farmer'.
     * A user with both 'farmer' and any other role (e.g. 'researcher', 'admin')
     * is allowed through — the non-farmer role grants legitimate admin access.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user  = auth()->user();
            $roles = $user->getRoleNames(); // Spatie collection of role name strings

            // Block only when farmer is the sole role.
            $isFarmerOnly = $roles->count() > 0
                && $roles->every(fn ($role) => $role === 'farmer');

            if ($isFarmerOnly) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Forbidden.'], 403);
                }
                abort(403, 'Farmer accounts cannot access the admin dashboard.');
            }
        }

        return $next($request);
    }
}
