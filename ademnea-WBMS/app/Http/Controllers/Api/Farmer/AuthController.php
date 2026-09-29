<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Farmer\{
    RegisterRequest,
    LoginRequest,
    ForgotPasswordRequest,
    ResetPasswordRequest
};
use App\Services\Farmer\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UC-FAPI-01 to 04.
 *
 * Routes: POST /api/v1/farmer/register
 *         POST /api/v1/farmer/login
 *         POST /api/v1/farmer/logout
 *         POST /api/v1/farmer/password/forgot
 *         POST /api/v1/farmer/password/reset
 *
 * These five endpoints deliberately do NOT use the App\Traits\ApiResponse
 * envelope. The SRS pins their bodies at the top level — the mobile client
 * reads `token` and `expires_at` directly, not `data.token` — so wrapping
 * them would break the published contract. REQ-F-FAPI-34's envelope still
 * governs every data endpoint in this namespace.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /** UC-FAPI-01 */
    public function register(RegisterRequest $request): JsonResponse
    {
        $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Registration submitted. Awaiting admin approval.',
        ], 201);
    }

    /** UC-FAPI-02 */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        // Null covers a wrong password, an unknown address and a non-farmer
        // account alike — the client cannot tell them apart.
        if ($result === null) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (isset($result['error'])) {
            return response()->json(['message' => $result['message']], 403);
        }

        return response()->json($result, 200);
    }

    /** UC-FAPI-03 */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.'], 200);
    }

    /** UC-FAPI-04 step 1 */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->sendResetLink($request->validated()['email']);
        } catch (\Throwable) {
            // Swallowed on purpose. The response below must be identical
            // whether the address exists, does not exist, or the mail
            // transport failed — any difference is an enumeration oracle.
            // AuthService already logs the failure.
        }

        return response()->json([
            'message' => 'If this email is registered, you will receive a reset link shortly.',
        ], 200);
    }

    /** UC-FAPI-04 step 2 */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $ok = $this->authService->resetPassword($request->validated());

        if (! $ok) {
            return response()->json([
                'message' => 'This password reset link is invalid or has expired. Please request a new one.',
            ], 422);
        }

        return response()->json([
            'message' => 'Password reset successfully. Please log in.',
        ], 200);
    }
}
