<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Farmer\DeviceTokenRequest;
use App\Http\Requests\Api\Farmer\ForgotPasswordRequest;
use App\Http\Requests\Api\Farmer\LoginRequest;
use App\Http\Requests\Api\Farmer\RegisterRequest;
use App\Http\Requests\Api\Farmer\ResetPasswordRequest;
use App\Http\Requests\Api\Farmer\UpdateProfileRequest;
use App\Services\Farmer\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Farmer mobile API: registration, login, password reset, profile, push token.
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->created([
            'id' => $result['farmer']->id,
            'name' => $result['user']->name,
            'email' => $result['user']->email,
            'status' => $result['user']->status,
        ], 'Registration submitted. Awaiting admin approval.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if ($result === null) {
            return $this->unauthorized('Invalid credentials.');
        }

        if (isset($result['error'])) {
            return $this->forbidden($result['message']);
        }

        return response()->json($result);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'Logged out successfully.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendResetLink($request->validated('email'));

        return $this->success(null, 'If that email is registered, a reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        if (! $this->authService->resetPassword($request->validated())) {
            return $this->error('The reset token is invalid or has expired.', 422);
        }

        return $this->success(null, 'Password reset successfully. Please log in.');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->success($this->authService->getProfile($request->user()));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $result = $this->authService->updateProfile($request->user(), $request->validated());

        return $this->success($this->authService->getProfile($result['user']), 'Profile updated successfully.');
    }

    public function registerDeviceToken(DeviceTokenRequest $request): JsonResponse
    {
        $this->authService->registerDeviceToken($request->user(), $request->validated('device_token'));

        return $this->success(null, 'Device token registered successfully.');
    }
}
