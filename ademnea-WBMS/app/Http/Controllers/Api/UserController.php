<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Api\UserController
 *
 * Sanctum-guarded API endpoint for user listing.
 * Authentication: auth:sanctum (applied at route-group level in routes/api.php)
 * Permission:     manage-users (applied at route-group level in routes/api.php)
 *
 * The EnsureNotFarmer middleware is NOT applied to API routes — farmers may
 * legitimately call API endpoints with their Sanctum token for mobile app access.
 *
 * REQ-F-RBAC-06 — API endpoints return JSON 401/403 on auth/permission failures.
 * REQ 15.1, 15.2, 15.3 — Sanctum-guarded, 401 for missing token, 403 for missing permission.
 */
class UserController extends Controller
{
    /**
     * Return a paginated JSON list of all non-deleted users.
     *
     * Supports the same search/role/status filters as the web UserController.
     *
     * GET /api/users
     * GET /api/users?search=john
     * GET /api/users?role=field-officer
     * GET /api/users?status=active
     * GET /api/users?page=2
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('roles');

        // Optional search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Optional role filter
        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        // Optional status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->orderBy('created_at', 'desc')
                       ->paginate(25);

        return response()->json([
            'data'  => $users->items(),
            'meta'  => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last'  => $users->url($users->lastPage()),
                'prev'  => $users->previousPageUrl(),
                'next'  => $users->nextPageUrl(),
            ],
        ]);
    }
}
