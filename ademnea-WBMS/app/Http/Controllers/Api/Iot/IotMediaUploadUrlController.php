<?php

namespace App\Http\Controllers\Api\Iot;

use App\Contracts\MediaUploadStorageContract;
use App\Http\Controllers\Controller;
use App\Services\IotDeviceAuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IotMediaUploadUrlController extends Controller
{
    public function __construct(
        private readonly IotDeviceAuthenticationService $auth,
        private readonly MediaUploadStorageContract $storage,
    ) {
    }

    public function requestUploadUrl(Request $request): JsonResponse
    {
        $device = $this->auth->resolveDevice($request->header('X-Api-Key', ''));

        if (! $device) {
            return response()->json(['message' => 'Unauthorized device.'], 401);
        }

        $validated = $request->validate([
            'media_type' => 'required|in:photo,video,audio',
            'content_type' => 'required|string',
        ]);

        $extension = str($validated['content_type'])->after('/')->toString();
        $objectKey = sprintf(
            'iot-media/%s/%s.%s',
            $device->device_code,
            now()->format('Y-m-d\TH-i-s\Z'),
            $extension
        );

        return response()->json([
            'upload_url' => $this->storage->generateUploadUrl($objectKey, $validated['content_type']),
            's3_object_key' => $objectKey,
            'expires_in_seconds' => 900,
        ]);
    }
}