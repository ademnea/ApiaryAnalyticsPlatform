<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Farmer\DeviceTokenRequest;
use App\Http\Requests\Api\Farmer\UpdateProfileRequest;
use App\Services\Farmer\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UC-FAPI-05 (view/edit profile) and UC-FAPI-14 (device token).
 *
 * Unlike the auth endpoints, these use the standard REQ-F-FAPI-34 envelope,
 * so the mobile client handles them like every other data endpoint.
 */
class ProfileController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService
    ) {}

    /** UC-FAPI-05 — view */
    public function show(Request $request): JsonResponse
    {
        return $this->success($this->authService->getProfile($request->user()));
    }

    /** UC-FAPI-05 — edit */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $result = $this->authService->updateProfile($request->user(), $request->validated());

        return $this->success(
            $this->authService->getProfile($result['user']),
            'Profile updated successfully.'
        );
    }

    /** UC-FAPI-14 — register the FCM device token for push delivery */
    public function storeDeviceToken(DeviceTokenRequest $request): JsonResponse
    {
        $this->authService->registerDeviceToken(
            $request->user(),
            $request->validated()['device_token']
        );

        // The token is never echoed back.
        return $this->success(null, 'Device token registered successfully.');
    }
}
