<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Iot\IotLocalMediaMockController;
use App\Http\Controllers\Api\Iot\IotMediaUploadUrlController;

//define all controllers used in the Farmer API routes
use App\Http\Controllers\Api\Farmer\AuthController;
use App\Http\Controllers\Api\Farmer\FarmController;
use App\Http\Controllers\Api\Farmer\SensorController;
use App\Http\Controllers\Api\Farmer\MediaController;
use App\Http\Controllers\Api\Farmer\InspectionController;
use App\Http\Controllers\Api\Farmer\AlertController;
use App\Http\Controllers\Api\Farmer\MessageController;

// media upload URL request for IoT devices, which will be used to upload media to S3 (or local mock storage).
Route::post('iot/media/upload-url', [IotMediaUploadUrlController::class, 'requestUploadUrl']);

// TEMPORARY — local S3-mock receiver, only used while IOT_MEDIA_DISK != s3.
Route::put('iot/media-mock/{key}', [IotLocalMediaMockController::class, 'receive'])
    ->name('iot.media.mock-upload')
    ->middleware('signed');

// Farmer Mobile API (Section 4.8) — Developer D's module



/*
|--------------------------------------------------------------------------
| API Routes - Version 1 (Farmer API)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/farmer')->group(function () {

    // Public routes (no authentication required)
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);

    // Authenticated routes
    Route::middleware(['auth:sanctum'])->group(function () {

        // Authentication
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/device-token', [AuthController::class, 'registerDeviceToken']);

        // Farms
        Route::get('/farms', [FarmController::class, 'index']);
        Route::get('/farms/{farmId}/hives', [FarmController::class, 'hives']);
        Route::get('/hives/{hiveId}', [FarmController::class, 'showHive']);

        // Sensor Data
        Route::get('/hives/{hiveId}/temperature', [SensorController::class, 'temperature']);
        Route::get('/hives/{hiveId}/humidity', [SensorController::class, 'humidity']);
        Route::get('/hives/{hiveId}/carbondioxide', [SensorController::class, 'carbondioxide']);
        Route::get('/hives/{hiveId}/weight', [SensorController::class, 'weight']);
        Route::get('/hives/{hiveId}/latest', [SensorController::class, 'latest']);

        // Media
        Route::get('/hives/{hiveId}/photos', [MediaController::class, 'photos']);
        Route::get('/hives/{hiveId}/audio', [MediaController::class, 'audio']);
        Route::get('/hives/{hiveId}/videos', [MediaController::class, 'videos']);

        // Inspections
        Route::get('/hives/{hiveId}/inspections', [InspectionController::class, 'index']);

        // Alerts
        Route::get('/alerts', [AlertController::class, 'index']);
        Route::patch('/alerts/{alertId}/read', [AlertController::class, 'markAsRead']);

        // Messages
        Route::get('/messages', [MessageController::class, 'index']);
        Route::post('/messages', [MessageController::class, 'store']);
    });
});
//Farmer Mobile API (Section 4.8) — Developer D's module
require __DIR__.'/farmer_api.php';
