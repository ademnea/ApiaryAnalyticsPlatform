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
| The v1/farmer route group lives entirely in farmer_api.php (Apiary-based).
|
| A duplicate legacy v1/farmer group used to be defined directly in this file
| (Farm-based, pre-Apiary-redesign). Because routes are matched in registration
| order and that group was registered BEFORE this require, it silently shadowed
| nearly every route in farmer_api.php — including sending /profile and
| /device-token to the wrong controller, dropping the role:farmer middleware,
| and routing PATCH /alerts/{id}/read at AlertController@markAsRead, a method
| that does not exist. It has been removed along with the Farm-based
| controllers and services it pointed at.
|
| Farmers' physical sites are modelled as Apiary; see /api/v1/farmer/apiaries.
|--------------------------------------------------------------------------
*/

// Farmer Mobile API (Section 4.8) — Developer D's module
require __DIR__.'/farmer_api.php';
