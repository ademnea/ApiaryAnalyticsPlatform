<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Farmer\RegisterRequest;
use App\Http\Requests\Api\Farmer\LoginRequest;
use App\Http\Requests\Api\Farmer\ForgotPasswordRequest;
use App\Http\Requests\Api\Farmer\ResetPasswordRequest;
use App\Http\Requests\Api\Farmer\UpdateProfileRequest;
use App\Http\Requests\Api\Farmer\DeviceTokenRequest;
use App\Services\Farmer\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new farmer (pending approval)
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->authService->register($data);

        return response()->json([
            'message' => 'Registration submitted. Awaiting admin approval.',
            'data' => [
                'user_id' => $result['user']->id,
                'email' => $result['user']->email,
                'status' => 'pending',
            ],
        ], 201);
    }

    /**
     * Login farmer
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $result = $this->authService->login($credentials);

        if (!$result) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['message'],
            ], $result['error'] === 'pending' ? 403 : 403);
        }

        return response()->json([
            'token' => $result['token'],
            'expires_at' => $result['expires_at'],
            'farmer' => $result['farmer'],
        ]);
    }

    /**
     * Logout farmer
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendResetLink($request->email);

        // Always return the same response to prevent enumeration
        return response()->json([
            'message' => 'If this email is registered, you will receive a reset link shortly.',
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $success = $this->authService->resetPassword($data);

        if (!$success) {
            return response()->json([
                'message' => 'This password reset link is invalid or has expired. Please request a new one.',
            ], 422);
        }

        return response()->json([
            'message' => 'Password reset successfully. Please log in.',
        ]);
    }

    /**
     * Get profile
     */
    public function profile(Request $request): JsonResponse
    {
        $profile = $this->authService->getProfile($request->user());

        return response()->json([
            'data' => $profile,
        ]);
    }

    /**
     * Update profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->authService->updateProfile($request->user(), $data);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => $this->authService->getProfile($result['user']),
        ]);
    }

    /**
     * Register FCM device token
     */
    public function registerDeviceToken(DeviceTokenRequest $request): JsonResponse
    {
        $this->authService->registerDeviceToken(
            $request->user(),
            $request->device_token
        );

        return response()->json([
            'message' => 'Device token registered successfully.',
        ]);
    }
}