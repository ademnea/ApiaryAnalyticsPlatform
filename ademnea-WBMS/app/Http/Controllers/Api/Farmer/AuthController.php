<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\Auth\{
    RegisterRequest,
    LoginRequest,
    ForgotPasswordRequest,
    ResetPasswordRequest
};
use App\Services\Farmer\FarmerAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UC-FAPI-01 to 04
 * Routes: POST /api/v1/farmer/register
 *         POST /api/v1/farmer/login
 *         POST /api/v1/farmer/logout
 *         POST /api/v1/farmer/password/forgot
 *         POST /api/v1/farmer/password/reset
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FarmerAuthService $authService
    ) {}

    /** REQ-F-FAPI-01 */
    public function register(RegisterRequest $request): JsonResponse
    {
        $farmer = $this->authService->register($request->validated());

        return $this->created([
            'farmer' => [
                'id'     => $farmer->id,
                'name'   => $farmer->name,
                'email'  => $farmer->email,
                'status' => $farmer->status,
            ],
        ], 'Registration successful. Your account is pending admin approval.');
    }

    /** REQ-F-FAPI-02 */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (!$result['success']) {
            return $this->error($result['message'], 401);
        }

        return $this->success([
            'token'  => $result['token'],
            'farmer' => [
                'id'        => $result['farmer']->id,
                'name'      => $result['farmer']->name,
                'email'     => $result['farmer']->email,
                'telephone' => $result['farmer']->telephone,
            ],
        ], 'Login successful.');
    }

    /** REQ-F-FAPI-03 */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'Logged out successfully.');
    }

    /** REQ-F-FAPI-04 step 1 */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink($request->email);

        // Always 200 — never confirm whether email exists
        return $this->success(null, 'If that email is registered, a reset link has been sent.');
    }

    /** REQ-F-FAPI-04 step 2 */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $ok = $this->authService->resetPassword($request->validated());

        if (!$ok) {
            return $this->error('The reset token is invalid or has expired.', 422);
        }

        return $this->success(null, 'Password reset successfully. Please log in.');
    }
}
