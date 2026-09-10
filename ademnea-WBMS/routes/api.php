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

/*
|--------------------------------------------------------------------------
| API Routes - Version 1 (Farmer API)
|--------------------------------------------------------------------------
| The actual v1/farmer route group lives in farmer_api.php (Apiary-based,
| current). A duplicate legacy v1/farmer group used to be defined directly
| in this file (Farm-based, pre-Apiary-redesign) and, because it was
| registered before this require, silently shadowed every route below —
| removed as part of retiring the Farm model in favour of Apiary.
|--------------------------------------------------------------------------
*/

// Farmer Mobile API (Section 4.8) — Developer D's module
require __DIR__.'/farmer_api.php';
