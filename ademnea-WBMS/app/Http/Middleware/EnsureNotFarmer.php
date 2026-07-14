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
     * Blocks any authenticated user whose only assigned role is 'farmer'
     * from accessing admin dashboard routes.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->hasRole('farmer')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            abort(403, 'Farmer accounts cannot access the admin dashboard.');
        }

        return $next($request);
    }
}
