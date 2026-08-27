<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Iot\IotLocalMediaMockController;
use App\Http\Controllers\Api\Iot\IotMediaUploadUrlController;

// media upload URL request for IoT devices, which will be used to upload media to S3 (or local mock storage).
Route::post('iot/media/upload-url', [IotMediaUploadUrlController::class, 'requestUploadUrl']);

// TEMPORARY — local S3-mock receiver, only used while IOT_MEDIA_DISK != s3.
Route::put('iot/media-mock/{key}', [IotLocalMediaMockController::class, 'receive'])
    ->name('iot.media.mock-upload')
    ->middleware('signed');

// Farmer Mobile API (Section 4.8) — Developer D's module
require __DIR__.'/farmer_api.php';
